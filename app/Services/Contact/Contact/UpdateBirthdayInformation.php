<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;

class UpdateBirthdayInformation extends BaseService
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
            'is_date_known' => 'required|boolean',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
            'is_age_based' => 'nullable|boolean',
            'age' => 'nullable|integer',
            'add_reminder' => 'nullable|boolean',
        ];
    }

    /**
     * Update the information about the birthday.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->contact->removeSpecialDate('birthdate');

        $this->manageBirthday($data);

        return $this->contact;
    }

    /**
     * Update birthday information depending on the type of information.
     *
     * @param array $data
     * @return SpecialDate|null
     */
    private function manageBirthday(array $data)
    {
        if ($data['is_date_known'] == false) {
            return;
        }

        $specialDate = new SpecialDate;

        if ($data['is_age_based'] == true) {
            $specialDate = $this->approximate($data);
        }

        if ($data['is_age_based'] == false) {
            $specialDate = $this->exact($data);
        }

        return $specialDate;
    }

    /**
     * Case where the birthday is approximate. That means the birthdate is based
     * on the estimated age of the contact.
     *
     * @param array $data
     */
    private function approximate(array $data)
    {
        return $this->contact->setSpecialDateFromAge('birthdate', $data['age']);
    }

    /**
     * Case where we have a year, month and day for the birthday.
     *
     * @param  array  $data
     * @return SpecialDate
     */
    private function exact(array $data)
    {
        $specialDate = $specialDate = $this->contact->setSpecialDate(
            'birthdate',
            (is_null($data['year']) ? 0 : $data['year']),
            $data['month'],
            $data['day']
        );

        $this->setReminder($data, $specialDate);

        return $specialDate;
    }

    /**
     * Set a reminder for the given special date, if required.
     *
     * @param array  $data
     * @param SpecialDate $specialDate
     */
    private function setReminder(array $data, SpecialDate $specialDate)
    {
        if ($data['add_reminder'] == true) {
            $specialDate->setReminder(
                'year',
                1,
                trans('people.people_add_birthday_reminder',
                ['name' => $this->contact->first_name])
            );
        }
    }
}
