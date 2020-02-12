<?php

namespace App\Services\Contact\Description;

use App\Models\Contact\Contact;
use Carbon\Carbon;
use App\Services\BaseService;

class SetPersonalDescription extends BaseService
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
            'description' => 'required|string|max:255',
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
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->description = $data['description'];
        $contact->save();

        return $contact;
    }
}
