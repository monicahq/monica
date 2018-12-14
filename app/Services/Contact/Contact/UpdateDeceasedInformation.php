<?php

namespace App\Services\Contact\Contact;

use App\Helpers\DateHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;

class UpdateDeceasedInformation extends BaseService
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
            'is_deceased' => 'required|boolean',
            'deceased_date' => 'nullable|date_format:Y-m-d',
            'is_age_based' => 'nullable|boolean',
            'is_year_unknown' => 'nullable|boolean',
            'age' => 'nullable|integer',
            'add_reminder' => 'nullable|boolean',
        ];
    }

    /**
     * Update the information about the deceased date.
     *
     * @param array $data
     * @return SpecialDate|null
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->contact->removeSpecialDate('deceased_date');

        return $this->manageDeceasedDate($data);
    }

    /**
     * Update deceased date information depending on the type of information.
     *
     * @param array $data
     * @return SpecialDate|null
     */
    private function manageDeceasedDate(array $data)
    {
        if ($data['is_deceased'] == false) {
            // remove all information about deceased date in the DB
            $this->contact->is_dead = false;
            $this->contact->save();

            return;
        }

        if ($data['is_age_based'] == true) {
            $specialDate = $this->approximate($data);
        }

        if ($data['is_age_based'] == false && $data['is_year_unknown'] == true) {
            $specialDate = $this->almost($data);
        }

        if ($data['is_age_based'] == false && $data['is_year_unknown'] == false) {
            $specialDate = $this->exact($data);
        }

        $this->contact->is_dead = true;
        $this->contact->save();

        return $specialDate;
    }

    /**
     * Case where the deceased date is approximate. That means the deceased date
     *  is based on the estimated age of the contact.
     *
     * @param array $data
     * @return void
     */
    private function approximate(array $data)
    {
        return $this->contact->setSpecialDateFromAge('deceased_date', $data['age']);
    }

    /**
     * Case where only the month and day are known, but not the year.
     *
     * @param array $data
     * @return void
     */
    private function almost(array $data)
    {
        $deceasedDate = $data['deceased_date'];
        $deceasedDate = DateHelper::parseDate($deceasedDate);
        $specialDate = $this->contact->setSpecialDate(
            'deceased_date',
            0,
            $deceasedDate->month,
            $deceasedDate->day
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
        $deceasedDate = $data['deceased_date'];
        $deceasedDate = DateHelper::parseDate($deceasedDate);
        $specialDate = $specialDate = $this->contact->setSpecialDate(
            'deceased_date',
            $deceasedDate->year,
            $deceasedDate->month,
            $deceasedDate->day
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
                trans(
                    'people.deceased_reminder_title',
                    ['name' => $this->contact->first_name]
                )
            );
        }
    }
}
