<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

class UpdateDeceasedInformation extends BaseService
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

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->clearRelatedReminder($contact);

        $this->clearRelatedSpecialDate($contact);

        $this->manageDeceasedDate($data, $contact);

        return $contact;
    }

    /**
     * Delete related reminder.
     *
     * @param Contact  $contact
     * @return void
     */
    private function clearRelatedReminder(Contact $contact)
    {
        if (is_null($contact->deceased_reminder_id)) {
            return;
        }

        app(DestroyReminder::class)->execute([
            'account_id' => $contact->account_id,
            'reminder_id' => $contact->deceased_reminder_id,
        ]);
    }

    /**
     * Delete related special date.
     *
     * @param Contact  $contact
     * @return void
     */
    private function clearRelatedSpecialDate(Contact $contact)
    {
        if (is_null($contact->deceased_special_date_id)) {
            return;
        }

        $specialDate = SpecialDate::find($contact->deceased_special_date_id);
        $specialDate->delete();
    }

    /**
     * Update deceased date information depending on the type of information.
     *
     * @param array $data
     * @param Contact $contact
     *
     * @return void
     */
    private function manageDeceasedDate(array $data, Contact $contact): void
    {
        if (! $data['is_deceased']) {
            // remove all information about deceased date in the DB
            $contact->is_dead = false;
            $contact->deceased_special_date_id = null;
            $contact->save();

            return;
        }

        $contact->is_dead = true;
        $contact->save();

        if ($data['is_date_known']) {
            $this->exact($data, $contact);
        }
    }

    /**
     * Case where we have a year, month and day for the date.
     *
     * @param  array  $data
     * @param Contact $contact
     * @return void
     */
    private function exact(array $data, Contact $contact)
    {
        $specialDate = $contact->setSpecialDate(
            'deceased_date',
            (is_null($data['year']) ? 0 : $data['year']),
            $data['month'],
            $data['day']
        );

        $this->setReminder($data, $contact, $specialDate);
    }

    /**
     * Set a reminder for the given special date, if required.
     *
     * @param array  $data
     * @param Contact $contact
     * @param SpecialDate $specialDate
     * @return void
     */
    private function setReminder(array $data, Contact $contact, SpecialDate $specialDate)
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
                    ['name' => $contact->first_name]
                ),
            ]);

            $contact->deceased_reminder_id = $reminder->id;
            $contact->save();
        }
    }
}
