<?php

namespace App;

use Auth;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use MartinJoiner\OrdinalNumber\OrdinalNumber;

class Reminder extends Model
{
    protected $dates = ['last_triggered', 'next_expected_date'];

    /**
     * Get the title of a reminder.
     * @return string
     */
    public function getTitle()
    {
        if (is_null($this->title)) {
            return null;
        }

        return $this->title;
    }

    /**
     * Get the description of a reminder.
     * @return string
     */
    public function getDescription()
    {
        if (is_null($this->description)) {
            return null;
        }

        return $this->description;
    }

    /**
     * Return the next expected date.
     *
     * @return string
     */
    public function getNextExpectedDate()
    {
        return $this->next_expected_date->format('Y-m-d');
    }

    /**
     * Calculate the next expected date for this reminder based on the current
     * start date.
     * @param  Carbon $startDate
     * @param  string $frequencyTYpe
     * @param  int $frequencyNumber
     * @return void
     */
    public function calculateNextExpectedDate($startDate, $frequencyType, $frequencyNumber)
    {
        if ($startDate->isToday()) {
            $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);
            $this->next_expected_date = $nextDate;
        }

        if ($startDate >= $startDate->tomorrow()) {
            $this->next_expected_date = $startDate;
        } else {

            // Date is in the past, we need to extract the month and day, and
            // setup the next occurence at those dates.
            $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);

            while ($nextDate->isPast()) {
                $nextDate = DateHelper::calculateNextOccuringDate($nextDate, $frequencyType, $frequencyNumber);
            }

            // This is the case where we set the date in the past, but the next
            // occuring date is still today, so we make sure it skips to the
            // next occuring date.
            if ($startDate->isToday()) {
                $nextDate = DateHelper::calculateNextOccuringDate($startDate, $frequencyType, $frequencyNumber);
            }
            $this->next_expected_date = $nextDate;
        }
    }
}
