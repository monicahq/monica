<?php

namespace App\Services\Contact\Contact;

use App\Helpers\DateHelper;
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
            'account_id'      => 'required|integer|exists:accounts,id',
            'contact_id'      => 'required|integer',
            'is_age_based'    => 'nullable|boolean',
            'is_year_unknown' => 'nullable|boolean',
            'age'             => 'nullable|integer',
            'birthdate'       => 'nullable|date_format:Y-m-d',
            'add_reminder'    => 'required|boolean',
        ];
    }

    /**
     * Update the information about the birthday.
     *
     * @param array $data
     * @return SpecialDate
     */
    public function execute(array $data) : SpecialDate
    {
        $this->validate($data);

        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->contact->removeSpecialDate('birthdate');

        return $this->manageBirthday($data);
    }

    /**
     * Update birthday information depending on the type of information.
     *
     * @param array $data
     * @return SpecialDate
     */
    private function manageBirthday(array $data)
    {
        if ($data['is_age_based'] == true) {
            return $this->approximate($data);
        }

        if ($data['is_age_based'] == false && $data['is_year_unknown'] == true) {
            return $this->almost($data);
        }

        if ($data['is_age_based'] == false && $data['is_year_unknown'] == false) {
            return $this->exact($data);
        }
    }

    /**
     * Case where the birthday is approximate. That means the birthdate is based
     * on the estimated age of the contact.
     *
     * @param array $data
     * @return void
     */
    private function approximate(array $data)
    {
        return $this->contact->setSpecialDateFromAge('birthdate', $data['age']);
    }

    /**
     * Case where only the month and day are known, but not the year.
     *
     * @param array $data
     * @return void
     */
    private function almost(array $data)
    {
        $birthdate = $data['birthdate'];
        $birthdate = DateHelper::parseDate($birthdate);
        $specialDate = $this->contact->setSpecialDate(
            'birthdate',
            0,
            $birthdate->month,
            $birthdate->day
        );

        $this->setReminder($data, $specialDate);

        return $specialDate;
    }

    /**
     * Case where we have a year, month and day for the birthday.
     *
     * @param  array  $data
     * @return void
     */
    private function exact(array $data)
    {
        $birthdate = $data['birthdate'];
        $birthdate = DateHelper::parseDate($birthdate);
        $specialDate = $specialDate = $this->contact->setSpecialDate(
            'birthdate',
            $birthdate->year,
            $birthdate->month,
            $birthdate->day
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
