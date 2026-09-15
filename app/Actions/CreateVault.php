<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Builder;

/**
 * Open a new compartment in the account.
 */
class CreateVault
{
    private Vault $vault;

    public function __construct(
        private readonly User $user,
        private string $name,
        private ?string $description = null,
        private ?string $colour = null,
    ) {}

    public function execute(): Vault
    {
        $this->sanitize();
        $this->refuseAboveTheCeiling();
        $this->create();
        $this->seedTheContactDefault();
        $this->log();

        return $this->vault;
    }

    private function refuseAboveTheCeiling(): void
    {
        $this->user->authorize('create', Vault::class);
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? Vault::DEFAULT_COLOUR);
    }

    private function create(): void
    {
        $positions = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->vault = Vault::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $this->name,
            'description' => $this->description,
            'colour' => $this->colour,
            'position' => ($positions->max() ?? 0) + 1,
            'is_default' => $positions->isEmpty(),
            'contact_count' => 0,
        ]);
    }

    private function seedTheContactDefault(): void
    {
        $template = Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', true)
            ->whereHas('structure', function (Builder $query): void {
                $query->where('applies_to', StructureAppliesToEnum::Contact)
                    ->whereNotNull('published_version_id');
            })
            ->first();

        if ($template === null) {
            return;
        }

        VaultDefaultTemplate::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'entity_type_id' => null,
            'structure_version_id' => $template->structure->published_version_id,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultCreation,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
