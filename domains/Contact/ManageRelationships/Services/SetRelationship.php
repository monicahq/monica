<?php

namespace App\Contact\ManageRelationships\Services;

use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\RelationshipType;
use App\Interfaces\ServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SetRelationship extends BaseService implements ServiceInterface
{
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
     * Set a relationship between two contacts.
     * When a relationship is created (father -> son), we need to create
     * the inverse relationship (son -> father) as well.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $otherContact = Contact::where('vault_id', $data['vault_id'])
            ->findOrFail($data['other_contact_id']);

        $relationshipType = RelationshipType::findOrFail($data['relationship_type_id']);
        if ($relationshipType->groupType->account_id != $data['account_id']) {
            throw new ModelNotFoundException;
        }

        // create the relationships
        $this->setRelationship($this->contact, $otherContact, $relationshipType);

        $this->log($otherContact, $relationshipType);
    }

    private function setRelationship(Contact $contact, Contact $otherContact, RelationshipType $relationshipType): void
    {
        $contact->relationships()->syncWithoutDetaching([
            $otherContact->id => [
                'relationship_type_id' => $relationshipType->id,
            ],
        ]);
    }

    private function log(Contact $otherContact, RelationshipType $relationshipType): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'other_contact_id' => $otherContact->id,
                'other_contact_name' => $otherContact->name,
                'relationship_name' => $relationshipType->name,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'relationship_set',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'relationship_name' => $relationshipType->name,
            ]),
        ])->onQueue('low');
    }
}
