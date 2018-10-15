<?php

namespace App\Services\Contact\Reminder;

use App\Helpers\DateHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use App\Exceptions\WrongValueException;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\MissingParameterException;

class CreateReminder extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     * Frequency: 'day', 'week', 'year', 'one_time'.
     * Date: string.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'contact_id',
        'date',
        'frequency_type',
        'frequency_number',
        'title',
        'description',
        'special_date_id',
    ];

    /**
     * Create a reminder.
     *
     * @param array $data
     * @return Reminder
     */
    public function execute(array $data) : Reminder
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if ($data['special_date_id']) {
            SpecialDate::where('account_id', $data['account_id'])
                ->findOrFail($data['special_date_id']);
        }

        $this->validateFrequency($data);

        $reminder = $this->attachReminderToLifeEvent($data);

        $reminder->calculateNextExpectedDate()->save();
        $reminder->scheduleNotifications();

        return $reminder;
    }

    /**
     * Actually create the reminder.
     *
     * @return Reminder
     */
    private function attachReminderToLifeEvent(array $data) : Reminder
    {
        $reminder = new Reminder;
        $reminder->frequency_type = $data['frequency_type'];
        $reminder->frequency_number = $data['frequency_number'];
        $reminder->next_expected_date = DateHelper::parseDate($data['date']);
        $reminder->special_date_id = $data['special_date_id'];
        $reminder->account_id = $data['account_id'];
        $reminder->contact_id = $data['contact_id'];
        $reminder->title = $data['title'];
        $reminder->description = $data['description'];
        $reminder->save();

        return $reminder;
    }

    /**
     * Make sure the frequency_type is in the range of authorized values.
     *
     * @param array $data
     */
    private function validateFrequency($data)
    {
        $validator = Validator::make($data, [
            'frequency_type' => [
                'required',
                Rule::in(['week', 'monk', 'year', 'one_time']),
            ],
        ]);

        if ($validator->fails()) {
            throw new WrongValueException('Authorized values: week, month, year, one_time');
        }
    }
}
