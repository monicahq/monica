<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

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
            'contact_id' => 'required|integer|exists:contacts,id',
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

        $this->clearRelatedReminder();

        $this->clearRelatedSpecialDate();

        $this->manageBirthday($data);

        return $this->contact;
    }

    /**
     * Delete related reminder.
     *
     * @return void
     */
    private function clearRelatedReminder()
    {
        if (is_null($this->contact->birthday_reminder_id)) {
            return;
        }

        app(DestroyReminder::class)->execute([
            'account_id' => $this->contact->account_id,
            'reminder_id' => $this->contact->birthday_reminder_id,
        ]);
    }

    /**
     * Delete related special date.
     *
     * @return void
     */
    private function clearRelatedSpecialDate()
    {
        if (is_null($this->contact->birthday_special_date_id)) {
            return;
        }

        $specialDate = SpecialDate::find($this->contact->birthday_special_date_id);
        $specialDate->delete();
    }

    /**
     * Update birthday information depending on the type of information.
     *
     * @param array $data
     * @return void|null
     */
    private function manageBirthday(array $data)
    {
        if (! $data['is_date_known']) {
            return;
        }

        if ($data['is_age_based']) {
            $this->approximate($data);
        } else {
            $this->exact($data);
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
        $this->contact->setSpecialDateFromAge('birthdate', $data['age']);
    }

    /**
     * Case where we have a year, month and day for the birthday.
     *
     * @param  array  $data
     * @return void
     */
    private function exact(array $data)
    {
        $specialDate = $this->contact->setSpecialDate(
            'birthdate',
            (is_null($data['year']) ? 0 : $data['year']),
            $data['month'],
            $data['day']
        );

        $this->setReminder($data, $specialDate);
    }

    /**
     * Set a reminder for the given special date, if required.
     *
     * @param array  $data
     * @param SpecialDate $specialDate
     * @return void
     */
    private function setReminder(array $data, SpecialDate $specialDate)
    {
        if (empty($data['add_reminder'])) {
            return;
        }

        if ($data['add_reminder']) {
            $reminder = app(CreateReminder::class)->execute([
                'account_id' => $data['account_id'],
                'contact_id' => $data['contact_id'],
                'initial_date' => $specialDate->date->toDateString(),
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'title' => trans(
                    'people.people_add_birthday_reminder',
                    ['name' => $this->contact->first_name]
                ),
                'delible' => false,
            ]);

            $this->contact->birthday_reminder_id = $reminder->id;
            $this->contact->save();
        }
    }
}
