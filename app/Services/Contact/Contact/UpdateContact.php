<?php

namespace App\Services\Contact\Contact;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Jobs\Avatars\GenerateDefaultAvatar;

class UpdateContact extends BaseService
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
            'contact_id' => 'required|integer',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'description' => 'nullable|string|max:255',
            'is_partial' => 'nullable|boolean',
            'is_birthdate_known' => 'required|boolean',
            'birthdate_day' => 'nullable|integer',
            'birthdate_month' => 'nullable|integer',
            'birthdate_year' => 'nullable|integer',
            'birthdate_is_age_based' => 'nullable|boolean',
            'birthdate_age' => 'nullable|integer',
            'birthdate_add_reminder' => 'nullable|boolean',
            'is_deceased' => 'nullable|boolean',
            'is_deceased_date_known' => 'required|boolean',
            'deceased_date_day' => 'nullable|integer',
            'deceased_date_month' => 'nullable|integer',
            'deceased_date_year' => 'nullable|integer',
            'deceased_date_add_reminder' => 'nullable|boolean',
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

        $this->updateGeneralInformation($data, $contact);

        $this->updateBirthDayInformation($data, $contact);

        $this->updateDeceasedInformation($data, $contact);

        // we query the DB again to fill the object with all the new properties
        $contact->refresh();

        return $contact;
    }

    /**
     * Update general information.
     *
     * @param array $data
     * @param Contact $contact
     * @return void
     */
    private function updateGeneralInformation(array $data, Contact $contact)
    {
        // filter out the data that shall not be updated here
        $dataOnly = Arr::except(
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

        $oldName = $contact->name;

        $contact->update($dataOnly);

        // only update the avatar if the name has changed
        if ($oldName != $contact->name) {
            GenerateDefaultAvatar::dispatch($contact);
        }
    }

    /**
     * Update the information about the birthday.
     *
     * @param array $data
     * @param Contact $contact
     * @return void
     */
    private function updateBirthDayInformation(array $data, Contact $contact)
    {
        app(UpdateBirthdayInformation::class)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $contact->id,
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
     * @param Contact $contact
     * @return void
     */
    private function updateDeceasedInformation(array $data, Contact $contact)
    {
        app(UpdateDeceasedInformation::class)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $contact->id,
            'is_deceased' => $data['is_deceased'],
            'is_date_known' => $data['is_deceased_date_known'],
            'day' => $this->nullOrvalue($data, 'deceased_date_day'),
            'month' => $this->nullOrvalue($data, 'deceased_date_month'),
            'year' => $this->nullOrvalue($data, 'deceased_date_year'),
            'add_reminder' => $this->nullOrvalue($data, 'deceased_date_add_reminder'),
        ]);
    }
}
