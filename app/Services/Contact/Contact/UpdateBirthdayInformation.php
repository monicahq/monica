<?php

namespace App\Services\Contact\Contact;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

class UpdateBirthdayInformation extends BaseService
{
    /**
     * @var array
     */
    public $data;

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
            'is_age_based' => 'nullable|boolean',
            'day' => [
                'integer',
                'nullable',
                Rule::requiredIf(function () {
                    return Arr::get($this->data, 'is_date_known', false) && ! Arr::get($this->data, 'is_age_based', false);
                }),
            ],
            'month' => [
                'integer',
                'nullable',
                Rule::requiredIf(function () {
                    return Arr::get($this->data, 'is_date_known', false) && ! Arr::get($this->data, 'is_age_based', false);
                }),
            ],
            'year' => 'nullable|integer',
            'age' => [
                'integer',
                'nullable',
                Rule::requiredIf(function () {
                    return Arr::get($this->data, 'is_date_known', false) && Arr::get($this->data, 'is_age_based', false);
                }),
            ],
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
        $this->data = $data;
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->clearRelatedReminder($contact);

        $this->clearRelatedSpecialDate($contact);

        $this->manageBirthday($data, $contact);

        return $contact;
    }

    /**
     * Delete related reminder.
     *
     * @param Contact $contact
     * @return void
     */
    private function clearRelatedReminder(Contact $contact)
    {
        if (is_null($contact->birthday_reminder_id)) {
            return;
        }

        app(DestroyReminder::class)->execute([
            'account_id' => $contact->account_id,
            'reminder_id' => $contact->birthday_reminder_id,
        ]);
    }

    /**
     * Delete related special date.
     *
     * @param Contact $contact
     * @return void
     */
    private function clearRelatedSpecialDate(Contact $contact)
    {
        if (is_null($contact->birthday_special_date_id)) {
            return;
        }

        $specialDate = SpecialDate::find($contact->birthday_special_date_id);
        $specialDate->delete();
    }

    /**
     * Update birthday information depending on the type of information.
     *
     * @param array $data
     * @param Contact $contact
     *
     * @return void
     */
    private function manageBirthday(array $data, Contact $contact): void
    {
        if (! $data['is_date_known']) {
            return;
        }

        if ($data['is_age_based']) {
            $this->approximate($data, $contact);
        } else {
            $this->exact($data, $contact);
        }
    }

    /**
     * Case where the birthday is approximate. That means the birthdate is based
     * on the estimated age of the contact.
     *
     * @param array $data
     * @param Contact $contact
     * @return void
     */
    private function approximate(array $data, Contact $contact)
    {
        $contact->setSpecialDateFromAge('birthdate', $data['age']);
    }

    /**
     * Case where we have a year, month and day for the birthday.
     *
     * @param  array  $data
     * @param Contact $contact
     * @return void
     */
    private function exact(array $data, Contact $contact)
    {
        $specialDate = $contact->setSpecialDate(
            'birthdate',
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
                    'people.people_add_birthday_reminder',
                    ['name' => $contact->first_name]
                ),
                'delible' => false,
            ]);

            $contact->birthday_reminder_id = $reminder->id;
            $contact->save();
        }
    }
}
