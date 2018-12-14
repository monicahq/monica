<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;

class UpdateContact extends BaseService
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
            'contact_id' => 'required|integer',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender_id' => 'required|integer|exists:genders,id',
            'description' => 'nullable|string|max:255',
            'is_partial' => 'nullable|boolean',
            'birthdate' => 'nullable|date_format:Y-m-d',
            'birthdate_is_age_based' => 'nullable|boolean',
            'birthdate_is_year_unknown' => 'nullable|boolean',
            'birthdate_age' => 'nullable|integer',
            'birthdate_add_reminder' => 'nullable|boolean',
            'is_dead' => 'nullable|boolean',
            'deceased_date' => 'nullable|date_format:Y-m-d',
            'deceased_date_is_age_based' => 'nullable|boolean',
            'deceased_date_is_year_unknown' => 'nullable|boolean',
            'deceased_date_age' => 'nullable|integer',
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

        // filter out the data that shall not be updated here
        $dataOnly = array_except(
            $data, [
                'birthdate',
                'birthdate_is_age_based',
                'birthdate_is_year_unknown',
                'birthdate_age',
                'birthdate_add_reminder',
                'is_dead',
                'deceased_date',
                'deceased_date_is_age_based',
                'deceased_date_is_year_unknown',
                'deceased_date_age',
                'deceased_date_add_reminder',
            ]
        );

        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $oldName = $this->contact->name;

        $this->contact->update($dataOnly);

        // only update the avatar if the name has changed
        if ($oldName != $this->contact) {
            $this->updateDefaultAvatar();
        }

        $this->updateBirthDayInformation($data);

        $this->updateDeceasedInformation($data);

        return $this->contact;
    }

    /**
     * Update the default avatar.
     *
     * @return void
     */
    private function updateDefaultAvatar()
    {
        $this->contact = (new GenerateDefaultAvatar)->execute([
            'contact_id' => $this->contact->id,
        ]);
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
            'is_age_based' => $data['birthdate_is_age_based'],
            'is_year_unknown' => $data['birthdate_is_year_unknown'],
            'age' => $data['birthdate_age'],
            'birthdate' => $data['birthdate'],
            'add_reminder' => $data['birthdate_add_reminder'],
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
        // TODO: cette fonction doit prendre en compte le parametre is_dead.
        // si c'est is_dead == false, il faut annuler l'ensemble des données
        // existantes sur la mort.

        (new UpdateBirthdayInformation)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $this->contact->id,
            'is_age_based' => $data['birthdate_is_age_based'],
            'is_year_unknown' => $data['birthdate_is_year_unknown'],
            'age' => $data['birthdate_age'],
            'birthdate' => $data['birthdate'],
            'add_reminder' => $data['birthdate_add_reminder'],
        ]);
    }
}
