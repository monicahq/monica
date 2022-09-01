<?php

namespace App\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\Template;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContact extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'maiden_name' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'pronoun_id' => 'nullable|integer|exists:pronouns,id',
            'template_id' => 'nullable|integer|exists:templates,id',
            'listed' => 'required|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     * @return Contact
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
            Gender::where('account_id', $this->data['account_id'])
                ->findOrFail($this->data['gender_id']);
        }

        if ($this->valueOrNull($this->data, 'pronoun_id')) {
            Pronoun::where('account_id', $this->data['account_id'])
                ->findOrFail($this->data['pronoun_id']);
        }

        if ($this->valueOrNull($this->data, 'template_id')) {
            Template::where('account_id', $this->data['account_id'])
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
            'template_id' => $templateId,
            'last_updated_at' => Carbon::now(),
            'listed' => $this->valueOrTrue($this->data, 'listed'),
        ]);
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
