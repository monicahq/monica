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
            'is_birthdate_known' => 'required|boolean',
            'birthdate_day' => 'nullable|integer',
            'birthdate_month' => 'nullable|integer',
            'birthdate_year' => 'nullable|integer',
            'birthdate_is_age_based' => 'nullable|boolean',
            'birthdate_age' => 'nullable|integer',
            'birthdate_add_reminder' => 'nullable|boolean',
            'is_deceased' => 'required|boolean',
            'is_deceased_date_known' => 'required|boolean',
            'deceased_date_day' => 'nullable|integer',
            'deceased_date_month' => 'nullable|integer',
            'deceased_date_year' => 'nullable|integer',
            'deceased_date_add_reminder' => 'nullable|boolean',
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

        // filter out the data that shall not be updated here
        $dataOnly = array_except(
            $data,
            [
                'is_birthdate_known',
                'birthdate_day',
                'birthdate_month',
                'birthdate_year',
                'birthdate_is_age_based',
                'birthdate_age',
                'birthdate_add_reminder',
                'is_deceased',
                'is_deceased_date_known',
                'deceased_date_day',
                'deceased_date_month',
                'deceased_date_year',
                'deceased_date_add_reminder',
            ]
        );

        $this->contact = Contact::create($dataOnly);

        $this->updateBirthDayInformation($data);

        $this->updateDeceasedInformation($data);

        $this->generateUUID();

        $this->contact->setAvatarColor();

        $this->contact->save();

        // we query the DB again to fill the object with all the new properties
        return Contact::find($this->contact->id);
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

    /**
     * Update the information about the birthday.
     *
     * @param array $data
     * @return void
     */
    private function updateBirthDayInformation(array $data)
    {
        (new UpdateBirthdayInformation)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $this->contact->id,
            'is_date_known' => $data['is_birthdate_known'],
            'day' => $this->nullOrvalue($data, 'birthdate_day'),
            'month' => $this->nullOrvalue($data, 'birthdate_month'),
            'year' => $this->nullOrvalue($data, 'birthdate_year'),
            'is_age_based' => $this->nullOrvalue($data, 'birthdate_is_age_based'),
            'age' => $this->nullOrvalue($data, 'birthdate_age'),
            'add_reminder' => $this->nullOrvalue($data, 'birthdate_add_reminder'),
        ]);
    }

    /**
     * Update the information about the date of death.
     *
     * @param array $data
     * @return void
     */
    private function updateDeceasedInformation(array $data)
    {
        (new UpdateDeceasedInformation)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $this->contact->id,
            'is_deceased' => $data['is_deceased'],
            'is_date_known' => $data['is_deceased_date_known'],
            'day' => $this->nullOrValue($data, 'deceased_date_day'),
            'month' => $this->nullOrValue($data, 'deceased_date_month'),
            'year' => $this->nullOrValue($data, 'deceased_date_year'),
            'add_reminder' => $this->nullOrValue($data, 'deceased_date_add_reminder'),
        ]);
    }
}
