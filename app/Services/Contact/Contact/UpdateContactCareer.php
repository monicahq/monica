<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;

class UpdateContactCareer extends BaseService
{
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
            'job' => 'required|nullable|string|max:255',
            'company' => 'required|nullable|string|max:255',
        ];
    }

    /**
     * Update a contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data) : Contact
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if ($contact->is_partial) {
            throw ValidationException::withMessages([
                'contact_id' => 'The contact can\'t be a partial contact',
            ]);
        }

        $contact->job = ! empty($data['job']) ? $data['job'] : null;
        $contact->company = ! empty($data['company']) ? $data['company'] : null;
        $contact->save();

        // we query the DB again to fill the object with all the new properties
        $contact->refresh();

        return $contact;
    }
}
