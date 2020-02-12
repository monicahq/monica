<?php

namespace App\Services\Contact\Description;

use App\Models\Contact\Contact;
use Carbon\Carbon;
use App\Services\BaseService;

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

        return $contact;
    }
}
