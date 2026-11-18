<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\FeaturedPossessionOrder;
use App\Helpers\PossessionDeclaredValue;
use App\Helpers\PossessionFieldValues;
use App\Helpers\PossessionTagNames;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Change what is known of a possession and, when given, its tags and the values of the fields of its
 * type, all written together or not at all. Its type never changes. A possession becoming featured is placed last among the featured possessions of its vault;
 * one that stops being featured leaves the order, which closes behind it.
 */
class UpdatePossession
{
    /** @var array<string, mixed> */
    private array $declaredValueAttributes;

    /** @var list<string>|null */
    private ?array $tags = null;

    /** @var Collection<string, PossessionField> */
    private Collection $fields;

    /**
     * @param  array<int, mixed>|null  $tagNames  null leaves the tags as they are
     * @param  array<string, mixed>|null  $values  keyed by the uuid of the field; null leaves the values as they are
     */
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private string $name,
        private ?string $description = null,
        private readonly ?Location $location = null,
        private ?string $identifier = null,
        private readonly PossessionStatusEnum $status = PossessionStatusEnum::Owned,
        private readonly int $quantity = 1,
        private readonly bool $isFeatured = false,
        private readonly ?string $declaredValue = null,
        private readonly ?CurrencyEnum $declaredValueCurrency = null,
        private readonly ?array $tagNames = null,
        private readonly ?array $values = null,
    ) {}

    public function execute(): Possession
    {
        $this->sanitize();
        $this->validate();
        $this->update();
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
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
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

        $this->tags = $this->tagNames === null ? null : PossessionTagNames::normalise($this->tagNames);

        if ($this->values !== null) {
            $this->fields = PossessionFieldValues::fields(possessionTypeId: $this->possession->possession_type_id, values: $this->values);
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            $this->updatePossession();
            $this->moveIfRelocated();

            if ($this->tags !== null) {
                PossessionTagNames::sync(user: $this->user, possession: $this->possession, names: $this->tags);
            }

            if ($this->values !== null) {
                PossessionFieldValues::write(possession: $this->possession, fields: $this->fields, values: $this->values);
            }
        });
    }

    private function moveIfRelocated(): void
    {
        if ($this->location?->id === $this->possession->current_location_id) {
            return;
        }

        $today = CarbonImmutable::parse(CarbonImmutable::now($this->user->timezone)->toDateString());

        if ($this->location instanceof Location) {
            new MovePossession(
                user: $this->user,
                possession: $this->possession,
                location: $this->location,
                movedOn: $today,
            )->execute();

            return;
        }

        PossessionLocationStay::query()
            ->where('possession_id', $this->possession->id)
            ->whereNull('left_on')
            ->update(['left_on' => $today->toDateString()]);

        $this->possession->update(['current_location_id' => null]);
    }

    private function updatePossession(): void
    {
        $wasFeatured = $this->possession->is_featured;

        $this->possession->update([
            'name' => $this->name,
            'description' => $this->description,
            'identifier' => $this->identifier,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'is_featured' => $this->isFeatured,
            'position' => $this->isFeatured ? $this->possession->position : null,
            ...$this->declaredValueAttributes,
        ]);

        if ($this->isFeatured && ! $wasFeatured) {
            FeaturedPossessionOrder::arrange(vaultId: $this->possession->vault_id, moved: $this->possession);
        }

        if (! $this->isFeatured && $wasFeatured) {
            FeaturedPossessionOrder::arrange(vaultId: $this->possession->vault_id);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionUpdate,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
