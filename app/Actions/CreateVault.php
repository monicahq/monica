<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Open a new compartment in the account, holding from the start the contact that stands for its
 * owner. The vault and that contact are written together, so neither exists without the other, and
 * that contact starts on the template the vault gives every new contact: the one chosen, or else
 * the one a new vault starts from.
 */
class CreateVault
{
    private Vault $vault;

    public function __construct(
        private readonly User $user,
        private string $name,
        private ?string $description = null,
        private ?string $colour = null,
        private readonly ?StructureVersion $structureVersion = null,
    ) {}

    public function execute(): Vault
    {
        $this->sanitize();
        $this->validate();
        $this->refuseAboveTheCeiling();

        DB::transaction(function (): void {
            $this->create();
            $this->seedTheContactDefault();
            $this->seedTheAnimalDefault();
            $this->createTheMeContact();
            $this->log();
        });

        return $this->vault;
    }

    private function validate(): void
    {
        if ($this->structureVersion === null) {
            return;
        }

        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->structureVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A record can only start on a published version.');
        }

        if ($this->structureVersion->structure->applies_to !== StructureAppliesToEnum::Contact) {
            throw new InvalidArgumentException('A vault only starts its contacts on a contact template.');
        }
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
        $versionId = $this->structureVersion->id ?? Template::startingPointFor(
            accountId: $this->user->account_id,
            appliesTo: StructureAppliesToEnum::Contact,
        )?->structure->published_version_id;

        if ($versionId === null) {
            return;
        }

        VaultDefaultTemplate::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'entity_type_id' => null,
            'structure_version_id' => $versionId,
        ]);
    }

    private function seedTheAnimalDefault(): void
    {
        $template = Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', true)
            ->where('slug', DefaultTemplateEnum::BasicAnimal->value)
            ->whereHas('structure', function (Builder $query): void {
                $query->whereNotNull('published_version_id');
            })
            ->first();

        if ($template === null) {
            return;
        }

        VaultDefaultTemplate::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'applies_to' => StructureAppliesToEnum::Entity,
            'entity_type_id' => $template->structure->entity_type_id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);
    }

    private function createTheMeContact(): void
    {
        new CreateContact(
            user: $this->user,
            vault: $this->vault,
            firstName: $this->user->first_name,
            lastName: $this->user->last_name,
            isMe: true,
        )->execute();
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
        )->afterCommit();
    }
}
