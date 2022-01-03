<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Services\QueuableService;
use App\Services\DispatchableService;
use App\Models\Relationship\Relationship;
use App\Services\Contact\Relationship\DestroyRelationship;

class DestroyContact extends BaseService implements QueuableService
{
    use DispatchableService;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'force_delete' => 'nullable|boolean',
        ];
    }

    /**
     * Destroy a contact.
     *
     * @param  array  $data
     * @return void
     */
    public function handle(array $data): void
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        $this->destroyRelationships($data, $contact);

        $contact->deleteAvatars();

        if ($this->valueOrFalse($data, 'force_delete') === true) {
            $contact->forceDelete();
        } else {
            $contact->delete();
        }
    }

    /**
     * Destroy all associated relationships.
     *
     * @param  array  $data
     * @param  Contact  $contact
     * @return void
     */
    private function destroyRelationships(array $data, Contact $contact)
    {
        $relationships = Relationship::where('contact_is', $contact->id)->get();
        $this->destroySpecificRelationships($data, $relationships);

        $relationships = Relationship::where('of_contact', $contact->id)->get();
        $this->destroySpecificRelationships($data, $relationships);
    }

    /**
     * Delete specific relationships.
     *
     * @param  array  $data
     * @param  \Illuminate\Support\Collection  $relationships
     * @return void
     */
    private function destroySpecificRelationships(array $data, $relationships)
    {
        foreach ($relationships as $relationship) {
            app(DestroyRelationship::class)
                ->execute([
                    'account_id' => $data['account_id'],
                    'relationship_id' => $relationship->id,
                ]);
        }
    }
}
