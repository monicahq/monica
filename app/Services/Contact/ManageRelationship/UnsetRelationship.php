<?php

namespace App\Services\Contact\ManageRelationship;

use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\RelationshipType;
use App\Interfaces\ServiceInterface;
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
            throw new ModelNotFoundException;
        }

        $this->unsetRelationship($this->contact, $otherContact);
        $this->unsetRelationship($otherContact, $this->contact);

        $this->log($otherContact);
    }

    private function unsetRelationship(Contact $contact, Contact $otherContact): void
    {
        $contact->relationships()->detach([
            $otherContact->id,
        ]);
    }

    private function log(Contact $otherContact): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'relationship_unset',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'relationship_name' => $this->relationshipType->name,
            ]),
        ])->onQueue('low');
    }
}
