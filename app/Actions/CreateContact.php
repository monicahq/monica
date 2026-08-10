<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\VaultAlreadyHasAMeContact;
use App\Exceptions\VaultHasNoContactDefault;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write somebody down. The name is all the product imposes, and everything else
 * the page will hold comes from the template version the contact is put on.
 *
 * The version is the vault's contact default unless the caller names another
 * one. The default is a version and never a template, so a contact written down
 * today does not silently follow whatever gets published tomorrow.
 *
 * The version has to be published. A draft is something somebody is still
 * editing, and a contact rendering from it would change under them; a retired
 * one is no longer offered for anything new.
 *
 * The three flags are the state the contact is born in, written in the same
 * insert as the rest of it. Changing any of them afterwards is its own action,
 * which is a different question from what somebody ticked on the form that
 * wrote them down.
 */
class CreateContact
{
    private Contact $contact;

    private TemplateVersion $version;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?TemplateVersion $templateVersion = null,
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
    ) {}

    public function execute(): Contact
    {
        $this->sanitize();
        $this->readTheVaultDefault();
        $this->validate();
        $this->create();
        $this->log();

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

    /**
     * Find the structure the vault starts a contact on, unless the caller named
     * one. The vault is checked here rather than in the validation that follows,
     * since reading the default of a vault in another account would answer a
     * question nobody is allowed to ask.
     */
    private function readTheVaultDefault(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->templateVersion instanceof TemplateVersion) {
            $this->version = $this->templateVersion;

            return;
        }

        $default = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->first();

        if ($default === null) {
            throw new VaultHasNoContactDefault('This vault has no template for new contacts to start from.');
        }

        $this->version = $default->templateVersion;
    }

    private function validate(): void
    {
        if ($this->version->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->version->template->applies_to !== TemplateAppliesToEnum::Contact) {
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

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->contact = Contact::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->vault->id,
                'template_version_id' => $this->version->id,
                'prefix' => $this->prefix,
                'first_name' => $this->firstName,
                'middle_name' => $this->middleName,
                'last_name' => $this->lastName,
                'suffix' => $this->suffix,
                'maiden_name' => $this->maidenName,
                'nickname' => $this->nickname,
                'name_order' => $this->nameOrder,
                'is_me' => $this->isMe,
                'is_partial' => $this->isPartial,
                'is_favourite' => $this->isFavourite,
                'avatar_colour' => Arr::random(Contact::AVATAR_COLOURS),
            ]);

            $this->vault->increment('contact_count');
            $this->version->increment('record_count');
        });
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
        )->onQueue('low');
    }
}
