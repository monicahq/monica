<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Helpers\PossessionDeclaredValue;
use App\Helpers\PossessionFieldValueFormat;
use App\Helpers\PossessionFieldValues;
use App\Helpers\PossessionTagNames;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionLocationStay;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Record something the account owns in a vault, with the values of the fields of its type, what it
 * would cost to replace and its tags, all written together or not at all. How it was acquired is a
 * transaction of its own. It is owned, counts one and is not
 * featured unless said otherwise; a featured one is placed last among the featured possessions.
 */
class CreatePossession
{
    private Possession $possession;

    /** @var Collection<string, PossessionField> */
    private Collection $fields;

    /** @var array<string, mixed> */
    private array $declaredValueAttributes;

    /** @var list<string> */
    private array $tags = [];

    /**
     * @param  array<string, mixed>  $values  keyed by the uuid of the field
     * @param  array<int, mixed>  $tagNames
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly PossessionType $possessionType,
        private string $name,
        private ?string $description = null,
        private readonly ?Location $location = null,
        private ?string $identifier = null,
        private readonly PossessionStatusEnum $status = PossessionStatusEnum::Owned,
        private readonly int $quantity = 1,
        private readonly bool $isFeatured = false,
        private readonly ?string $declaredValue = null,
        private readonly ?CurrencyEnum $declaredValueCurrency = null,
        private readonly array $values = [],
        private readonly array $tagNames = [],
    ) {}

    public function execute(): Possession
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->possession;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = $this->description === null ? null : TextSanitizer::plainText($this->description);
        $this->identifier = $this->identifier === null ? null : TextSanitizer::plainText($this->identifier);
        $this->description = $this->description === '' ? null : $this->description;
        $this->identifier = $this->identifier === '' ? null : $this->identifier;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }

        if ($this->location !== null && $this->location->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages(['name' => __('Write a name for this possession.')]);
        }

        $this->declaredValueAttributes = PossessionDeclaredValue::from(
            declaredValue: $this->declaredValue,
            declaredValueCurrency: $this->declaredValueCurrency,
        );

        $this->fields = PossessionFieldValues::fields(possessionTypeId: $this->possessionType->id, values: $this->values);

        $this->tags = PossessionTagNames::normalise($this->tagNames);
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->createPossession();
            $this->openStay();
            $this->writeValues();

            PossessionTagNames::sync(user: $this->user, possession: $this->possession, names: $this->tags);
        });
    }

    private function createPossession(): void
    {
        $this->possession = Possession::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'possession_type_id' => $this->possessionType->id,
            'current_location_id' => $this->location?->id,
            'name' => $this->name,
            'description' => $this->description,
            'identifier' => $this->identifier,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'is_featured' => $this->isFeatured,
            ...$this->declaredValueAttributes,
        ]);

        if ($this->isFeatured) {
            FeaturedPossessionOrder::arrange(vaultId: $this->vault->id, moved: $this->possession);
        }
    }

    private function openStay(): void
    {
        if ($this->location === null) {
            return;
        }

        PossessionLocationStay::query()->create([
            'account_id' => $this->possession->account_id,
            'vault_id' => $this->possession->vault_id,
            'possession_id' => $this->possession->id,
            'location_id' => $this->location->id,
            'arrived_on' => CarbonImmutable::now($this->user->timezone)->toDateString(),
            'left_on' => null,
        ]);
    }

    private function writeValues(): void
    {
        foreach ($this->values as $uuid => $value) {
            if (PossessionFieldValueFormat::isEmpty($value)) {
                continue;
            }

            /** @var PossessionField $field */
            $field = $this->fields->get((string) $uuid);

            PossessionFieldValue::query()->create([
                'account_id' => $this->possession->account_id,
                'possession_id' => $this->possession->id,
                'possession_field_id' => $field->id,
                'value' => PossessionFieldValueFormat::normalise($field, $value),
            ]);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionCreation,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
