<?php

namespace App\Services\Contact\Description;

use App\Models\User\User;
use App\Services\BaseService;
use function Safe\json_encode;
use App\Models\Contact\Contact;
use App\Jobs\AuditLog\LogAccountAudit;

class SetPersonalDescription extends BaseService
{
    private array $data;
    private Contact $contact;

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
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Set a contact's description.
     * The description should be saved as unparsed markdown content, and fetched
     * as unparsed markdown content. The UI is responsible for parsing and
     * displaying the proper content.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate($this->data);

        /* @var Contact */
        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->contact->description = $data['description'];
        $this->contact->save();

        $this->log();

        return $this->contact->refresh();
    }

    /**
     * Add an audit log.
     *
     * @return void
     */
    private function log(): void
    {
        $author = User::find($this->data['author_id']);

        LogAccountAudit::dispatch([
            'action' => 'contact_description_updated',
            'account_id' => $author->account_id,
            'about_contact_id' => $this->contact->id,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'audited_at' => now(),
            'should_appear_on_dashboard' => true,
            'objects' => json_encode([
                'contact_name' => $this->contact->name,
                'contact_id' => $this->contact->id,
            ]),
        ]);
    }
}
