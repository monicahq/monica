<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContact extends BaseService implements ServiceInterface
{
    private array $data;

    private Gender $gender;

    private Pronoun $pronoun;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'maiden_name' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'pronoun_id' => 'nullable|integer|exists:pronouns,id',
            'suffix' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:255',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a contact.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->contact->first_name = $data['first_name'];
        $this->contact->last_name = $this->valueOrNull($data, 'last_name');
        $this->contact->middle_name = $this->valueOrNull($data, 'middle_name');
        $this->contact->maiden_name = $this->valueOrNull($data, 'maiden_name');
        $this->contact->nickname = $this->valueOrNull($data, 'nickname');
        $this->contact->suffix = $this->valueOrNull($data, 'suffix');
        $this->contact->prefix = $this->valueOrNull($data, 'prefix');
        if ($this->valueOrNull($this->data, 'gender_id')) {
            $this->contact->gender_id = $this->gender->id;
        } else {
            $this->contact->gender_id = null;
        }
        if ($this->valueOrNull($this->data, 'pronoun_id')) {
            $this->contact->pronoun_id = $this->pronoun->id;
        } else {
            $this->contact->pronoun_id = null;
        }
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'gender_id')) {
            $this->gender = $this->account()->genders()
                ->findOrFail($this->data['gender_id']);
        }

        if ($this->valueOrNull($this->data, 'pronoun_id')) {
            $this->pronoun = $this->account()->pronouns()
                ->findOrFail($this->data['pronoun_id']);
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
            'action' => ContactFeedItem::ACTION_INFORMATION_UPDATED,
        ]);
    }
}
