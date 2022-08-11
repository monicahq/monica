<?php

namespace App\Contact\ManageRelationships\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UnsetRelationship extends BaseService implements ServiceInterface
{
    private RelationshipType $relationshipType;

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
            'relationship_type_id' => 'required|integer|exists:relationship_types,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'other_contact_id' => 'required|integer|exists:contacts,id',
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
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Unset an existing relationship between two contacts.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $otherContact = Contact::where('vault_id', $data['vault_id'])
            ->findOrFail($data['other_contact_id']);

        $this->relationshipType = RelationshipType::findOrFail($data['relationship_type_id']);
        if ($this->relationshipType->groupType->account_id != $data['account_id']) {
            throw new ModelNotFoundException();
        }

        $this->unsetRelationship($this->contact, $otherContact);
        $this->unsetRelationship($otherContact, $this->contact);

        if (! $this->contact->listed) {
            $this->contact->delete();
        }

        if (! $otherContact->listed) {
            $otherContact->delete();
        }

        $this->updateLastEditedDate();
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function unsetRelationship(Contact $contact, Contact $otherContact): void
    {
        $contact->relationships()->detach([
            $otherContact->id,
        ]);
    }
}
