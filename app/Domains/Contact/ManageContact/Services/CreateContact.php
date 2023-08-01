<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContact extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'id' => 'nullable|string',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'maiden_name' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'pronoun_id' => 'nullable|integer|exists:pronouns,id',
            'template_id' => 'nullable|integer|exists:templates,id',
            'listed' => 'required|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Create a contact.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;

        $this->validate();
        $this->createContact();
        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'gender_id')) {
            $this->account()->genders()
                ->findOrFail($this->data['gender_id']);
        }

        if ($this->valueOrNull($this->data, 'pronoun_id')) {
            $this->account()->pronouns()
                ->findOrFail($this->data['pronoun_id']);
        }

        if ($this->valueOrNull($this->data, 'template_id')) {
            $this->account()->templates()
                ->findOrFail($this->data['template_id']);
        }
    }

    private function createContact(): void
    {
        // template - if no template is provided, we should use the default
        // template that is in the vault - if it exists.
        $templateId = $this->valueOrNull($this->data, 'template_id');
        if (! $templateId) {
            $templateId = $this->vault->default_template_id;
        }

        $this->contact = Contact::create([
            'vault_id' => $this->data['vault_id'],
            'first_name' => $this->valueOrNull($this->data, 'first_name'),
            'last_name' => $this->valueOrNull($this->data, 'last_name'),
            'middle_name' => $this->valueOrNull($this->data, 'middle_name'),
            'nickname' => $this->valueOrNull($this->data, 'nickname'),
            'maiden_name' => $this->valueOrNull($this->data, 'maiden_name'),
            'gender_id' => $this->valueOrNull($this->data, 'gender_id'),
            'pronoun_id' => $this->valueOrNull($this->data, 'pronoun_id'),
            'suffix' => $this->valueOrNull($this->data, 'suffix'),
            'prefix' => $this->valueOrNull($this->data, 'prefix'),
            'template_id' => $templateId,
            'last_updated_at' => Carbon::now(),
            'listed' => $this->valueOrTrue($this->data, 'listed'),
        ]);
        if (($id = $this->valueOrNull($this->data, 'id')) !== null) {
            $this->contact->id = $id;
        }
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_CREATED,
        ]);
    }
}
