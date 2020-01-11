<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;

class UpdateContactFoodPreferences extends BaseService
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
            'food_preferences' => 'nullable|string|max:65535',
        ];
    }

    /**
     * Update the food preferences of the given contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if ($contact->is_partial) {
            throw ValidationException::withMessages([
                'contact_id' => 'The contact can\'t be a partial contact',
            ]);
        }

        $contact->food_preferences = ! empty($data['food_preferences']) ? $data['food_preferences'] : null;
        $contact->save();

        // we query the DB again to fill the object with all the new properties
        $contact->refresh();

        return $contact;
    }
}
