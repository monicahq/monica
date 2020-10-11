<?php

namespace App\Services\Contact\Description;

use App\Models\User\User;
use App\Services\BaseService;
use function Safe\json_encode;
use App\Models\Contact\Contact;
use App\Jobs\AuditLog\LogAccountAudit;

class ClearPersonalDescription extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'author_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Clear a contact's description.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->description = null;
        $contact->save();

        $this->log($data, $contact);

        return $contact;
    }

    /**
     * Add an audit log.
     *
     * @param array $data
     * @param Contact $contact
     * @return void
     */
    private function log(array $data, Contact $contact): void
    {
        $author = User::find($data['author_id']);

        LogAccountAudit::dispatch([
            'action' => 'contact_description_cleared',
            'account_id' => $author->account_id,
            'about_contact_id' => $contact->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => now(),
            'should_appear_on_dashboard' => true,
            'objects' => json_encode([
                'contact_name' => $contact->name,
                'contact_id' => $contact->id,
            ]),
        ]);
    }
}
