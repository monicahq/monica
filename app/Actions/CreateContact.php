<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\VaultAlreadyHasAMeContact;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write somebody down. The name is all the product imposes, and everything else
 * the page will hold comes from the template version the contact is put on.
 *
 * The version is given rather than looked up. Which structure a new contact
 * starts on is a default held per vault and per kind of record, and until that
 * model exists the caller says which version, so that nothing here quietly
 * picks the latest one and breaks the promise that a record follows the version
 * it was assigned.
 *
 * The version has to be published. A draft is something somebody is still
 * editing, and a contact rendering from it would change under them; a retired
 * one is no longer offered for anything new.
 */
class CreateContact
{
    private Contact $contact;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly TemplateVersion $templateVersion,
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
    ) {}

    public function execute(): Contact
    {
        $this->sanitize();
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

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        if ($this->templateVersion->template->applies_to !== TemplateAppliesToEnum::Contact) {
            throw new InvalidArgumentException('This template does not describe a contact.');
        }

        if ($this->templateVersion->status !== VersionStatusEnum::Published) {
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
                'template_version_id' => $this->templateVersion->id,
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
                'avatar_colour' => Arr::random(Contact::AVATAR_COLOURS),
            ]);

            $this->vault->increment('contact_count');
            $this->templateVersion->increment('record_count');
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
