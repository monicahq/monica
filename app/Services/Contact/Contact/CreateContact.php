<?php

namespace App\Services\Contact\Contact;

use App\Helpers\RandomHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class CreateContact extends BaseService
{
    private $contact;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender_id' => 'required|integer|exists:genders,id',
            'description' => 'nullable|string|max:255',
            'is_partial' => 'nullable|boolean',
        ];
    }

    /**
     * Create a contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data) : Contact
    {
        $this->validate($data);

        $this->contact = Contact::create($data);

        $this->generateUUID();

        $this->contact->setAvatarColor();

        $this->contact->save();

        return $this->contact;
    }

    /**
     * Generates a UUID for this contact.
     *
     * @return void
     */
    private function generateUUID()
    {
        $this->contact->uuid = RandomHelper::uuid();
        $this->contact->save();
    }
}
