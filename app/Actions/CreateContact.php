<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactNameKindEnum;
use App\Enums\NameOrderEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\VaultAlreadyHasAMeContact;
use App\Exceptions\VaultHasNoContactDefault;
use App\Helpers\TextSanitizer;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write somebody down. The name parts become the first way the name is written, and the one it is
 * shown under; a caller holding several ways passes them as a list instead.
 */
class CreateContact
{
    private Contact $contact;

    private StructureVersion $version;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?StructureVersion $structureVersion = null,
        private ?string $prefix = null,
        private ?string $firstName = null,
        private ?string $middleName = null,
        private ?string $lastName = null,
        private ?string $suffix = null,
        private ?string $maidenName = null,
        private ?string $nickname = null,
        private readonly ?NameOrderEnum $nameOrder = null,
        private readonly bool $isMe = false,
        private readonly bool $isPartial = false,
        private readonly bool $isFavourite = false,
        private readonly ?array $nameRepresentations = null,
        private readonly ?int $display = null,
    ) {}

    public function execute(): Contact
    {
        $this->sanitize();
        $this->readTheVaultDefault();
        $this->validate();
        $this->refuseAboveTheCeiling();
        $this->create();
        $this->log();
        $this->lookForDuplicates();

        return $this->contact;
    }

    private function sanitize(): void
    {
        $this->prefix = TextSanitizer::nullablePlainText($this->prefix);
        $this->firstName = TextSanitizer::nullablePlainText($this->firstName);
        $this->middleName = TextSanitizer::nullablePlainText($this->middleName);
        $this->lastName = TextSanitizer::nullablePlainText($this->lastName);
        $this->suffix = TextSanitizer::nullablePlainText($this->suffix);
        $this->maidenName = TextSanitizer::nullablePlainText($this->maidenName);
        $this->nickname = TextSanitizer::nullablePlainText($this->nickname);
    }

    private function readTheVaultDefault(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->structureVersion instanceof StructureVersion) {
            $this->version = $this->structureVersion;

            return;
        }

        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->first();

        if ($default === null) {
            throw new VaultHasNoContactDefault('This vault has no template for new contacts to start from.');
        }

        $this->version = $default->structureVersion;
    }

    private function validate(): void
    {
        if ($this->version->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->version->structure->applies_to !== StructureAppliesToEnum::Contact) {
            throw new InvalidArgumentException('This template does not describe a contact.');
        }

        if ($this->version->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A contact can only be put on a published version.');
        }

        if (! $this->isMe) {
            return;
        }

        $alreadyThere = Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('is_me', true)
            ->exists();

        if ($alreadyThere) {
            throw new VaultAlreadyHasAMeContact('This vault already holds the contact representing you.');
        }
    }

    private function refuseAboveTheCeiling(): void
    {
        if ($this->isMe) {
            return;
        }

        if ($this->isPartial) {
            return;
        }

        $this->user->authorize('create', Contact::class);
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->contact = Contact::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->vault->id,
                'structure_version_id' => $this->version->id,
                'maiden_name' => $this->maidenName,
                'nickname' => $this->nickname,
                'is_me' => $this->isMe,
                'is_partial' => $this->isPartial,
                'is_favourite' => $this->isFavourite,
                'avatar_colour' => Arr::random(Contact::AVATAR_COLOURS),
            ]);

            $this->writeTheName();

            $this->vault->increment('contact_count');
            $this->version->increment('record_count');

            new WriteDefaultValues(user: $this->user, record: $this->contact)->execute();
        });
    }

    private function writeTheName(): void
    {
        $nameRepresentations = $this->nameRepresentations ?? $this->nameFromTheParts();

        if ($nameRepresentations === []) {
            return;
        }

        new UpdateContactNameRepresentations(
            user: $this->user,
            contact: $this->contact,
            nameRepresentations: $nameRepresentations,
            display: $this->nameRepresentations === null ? 0 : $this->display,
            nickname: $this->nickname,
            maidenName: $this->maidenName,
            logsAndLooksForDuplicates: false,
        )->execute();
    }

    /** @return list<array<string, mixed>> */
    private function nameFromTheParts(): array
    {
        $parts = [$this->prefix, $this->firstName, $this->middleName, $this->lastName, $this->suffix];

        if (array_filter($parts) === []) {
            return [];
        }

        return [[
            'kind' => ContactNameKindEnum::Written,
            'prefix' => $this->prefix,
            'given_name' => $this->firstName,
            'additional_name' => $this->middleName,
            'family_name' => $this->lastName,
            'suffix' => $this->suffix,
            'name_order' => $this->nameOrder,
        ]];
    }

    private function lookForDuplicates(): void
    {
        DetectDuplicateContacts::dispatch($this->contact)->onQueue('low')->afterCommit();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactCreation,
            vault: $this->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        )->onQueue('low')->afterCommit();
    }
}
