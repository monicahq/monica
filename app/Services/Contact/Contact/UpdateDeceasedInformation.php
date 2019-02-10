<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

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
            'is_date_known' => 'required|boolean',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
            'add_reminder' => 'nullable|boolean',
        ];
    }

    /**
     * Update the information about the deceased date.
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

        $this->manageDeceasedDate($data);

        return $this->contact;
    }

    /**
     * Delete related reminder.
     *
     * @return void
     */
    private function clearRelatedReminder()
    {
        if (is_null($this->contact->deceased_reminder_id)) {
            return;
        }

        app(DestroyReminder::class)->execute([
            'account_id' => $this->contact->account_id,
            'reminder_id' => $this->contact->deceased_reminder_id,
        ]);
    }

    /**
     * Delete related special date.
     *
     * @return void
     */
    private function clearRelatedSpecialDate()
    {
        if (is_null($this->contact->deceased_special_date_id)) {
            return;
        }

        $specialDate = SpecialDate::find($this->contact->deceased_special_date_id);
        $specialDate->delete();
    }

    /**
     * Update deceased date information depending on the type of information.
     *
     * @param array $data
     * @return void|null
     */
    private function manageDeceasedDate(array $data)
    {
        if (! $data['is_deceased']) {
            // remove all information about deceased date in the DB
            $this->contact->is_dead = false;
            $this->contact->save();

            return;
        }

        $this->contact->is_dead = true;
        $this->contact->save();

        if ($data['is_date_known']) {
            $this->exact($data);
        }
    }

    /**
     * Case where we have a year, month and day for the date.
     *
     * @param  array  $data
     * @return void
     */
    private function exact(array $data)
    {
        $specialDate = $specialDate = $this->contact->setSpecialDate(
            'deceased_date',
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
                    'people.deceased_reminder_title',
                    ['name' => $this->contact->first_name]
                ),
            ]);

            $this->contact->deceased_reminder_id = $reminder->id;
            $this->contact->save();
        }
    }
}
