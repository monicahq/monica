<?php

namespace App\Services\Contact\Contact;

use App\Helpers\DateHelper;
use Illuminate\Support\Arr;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\DestroyReminder;

class UpdateContactIntroduction extends BaseService
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
            'met_through_contact_id' => 'nullable|integer|exists:contacts,id',
            'general_information' => 'nullable|string|max:65535',
            'where' => 'nullable|string|max:255',
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
     * Update the information about how a contact was introduced.
     *
     * @param  array  $data
     * @return Contact
     *
     * @throws ValidationException
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate($data);

        /** @var Contact */
        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        if ($contact->is_partial) {
            throw ValidationException::withMessages([
                'contact_id' => 'The contact can\'t be a partial contact',
            ]);
        }

        if ($metContactId = Arr::get($data, 'met_through_contact_id')) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($metContactId);
        }

        $this->setMetThroughContact($data, $contact);
        $this->clearRelatedReminder($contact);
        $this->manageDate($data, $contact);
        $this->setInformation($data, $contact);

        // we query the DB again to fill the object with all the new properties
        $contact->refresh();

        return $contact;
    }

    private function setMetThroughContact(array $data, Contact $contact): void
    {
        $contact->first_met_through_contact_id = Arr::get($data, 'met_through_contact_id');
        $contact->save();
    }

    private function setInformation(array $data, Contact $contact): void
    {
        $contact->first_met_additional_info = Arr::get($data, 'general_information');
        $contact->first_met_where = Arr::get($data, 'where');
        $contact->save();
    }

    private function clearRelatedReminder(Contact $contact): void
    {
        try {
            app(DestroyReminder::class)->execute([
                'account_id' => $contact->account_id,
                'reminder_id' => $contact->first_met_reminder_id,
            ]);
        } catch (\Exception $e) {
            // Ignore this error
        }
    }

    /**
     * Update date information depending on the type of information.
     *
     * @param  array  $data
     * @param  Contact  $contact
     * @return void
     */
    private function manageDate(array $data, Contact $contact): void
    {
        if (! $data['is_date_known']) {
            $contact->firstMetDate()->delete();

            return;
        }

        if (Arr::get($data, 'is_age_based')) {
            $this->approximate($data, $contact);
        } else {
            $this->exact($data, $contact);
        }
    }

    /**
     * Case where the date is approximate. That means the date is based
     * on the estimated age of the contact.
     *
     * @param  array  $data
     * @param  Contact  $contact
     * @return void
     */
    private function approximate(array $data, Contact $contact): void
    {
        $contact->setSpecialDateFromAge('first_met', $data['age']);
    }

    /**
     * Case where we have a year, month and day for the date.
     *
     * @param  array  $data
     * @param  Contact  $contact
     * @return void
     */
    private function exact(array $data, Contact $contact): void
    {
        $specialDate = $contact->setSpecialDate(
            'first_met',
            (is_null($data['year']) ? 0 : $data['year']),
            $data['month'],
            $data['day']
        );

        $this->setReminder($data, $contact, $specialDate);
    }

    /**
     * Set a reminder for the given special date, if required.
     *
     * @param  array  $data
     * @param  Contact  $contact
     * @param  SpecialDate  $specialDate
     * @return void
     */
    private function setReminder(array $data, Contact $contact, SpecialDate $specialDate): void
    {
        if (empty($data['add_reminder'])) {
            return;
        }

        $reminder = app(CreateReminder::class)->execute([
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'initial_date' => DateHelper::getDate($specialDate),
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => trans(
                'people.introductions_reminder_title',
                ['name' => $contact->first_name]
            ),
            'delible' => false,
        ]);

        $contact->first_met_reminder_id = $reminder->id;
        $contact->save();
    }
}
