<?php

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertDatesToUtc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // All dates are stored in a timezone not necessarily in UTC in the DB.
        // This makes the date comparison really weak and error prone.
        // We need to migrate all dates in UTC.

        if (env('APP_DEFAULT_TIMEZONE') == 'UTC') {
            return;
        }

        User::chunk(200, function ($users) {
            foreach ($users as $user) {
                $timezone = $user->timezone;

                // activities
                foreach ($user->account->activities as $activity) {
                    //\Log::info('timezone: '.$timezone.' | date it happened: '.$activity->date_it_happened);
                    if (! is_null($activity->date_it_happened)) {
                        $date = self::convertToUTC($activity->date_it_happened, $timezone);
                        $activity->date_it_happened = $date;
                    }
                    $activity->save();
                }

                // calls
                foreach ($user->account->calls as $call) {
                    if (! is_null($call->called_at)) {
                        $date = self::convertToUTC($call->called_at, $timezone);
                        $call->called_at = $date;
                    }
                    $call->save();
                }

                // contacts
                foreach ($user->account->contacts as $contact) {
                    if (! is_null($contact->last_consulted_at)) {
                        $date = self::convertToUTC($contact->last_consulted_at, $timezone);
                        $contact->last_consulted_at = $date;
                    }
                    $contact->save();
                }

                // gifts
                foreach ($user->account->gifts as $gift) {
                    if (! is_null($contact->offered_at)) {
                        $date = self::convertToUTC($contact->offered_at, $timezone);
                        $gift->offered_at = $date;
                    }
                    if (! is_null($contact->received_at)) {
                        $date = self::convertToUTC($contact->received_at, $timezone);
                        $gift->received_at = $date;
                    }
                    $gift->save();
                }

                // importJobs
                foreach ($user->account->importJobs as $importJob) {
                    if (! is_null($importJob->started_at)) {
                        $date = self::convertToUTC($importJob->started_at, $timezone);
                        $importJob->started_at = $date;
                    }
                    if (! is_null($importJob->ended_at)) {
                        $date = self::convertToUTC($importJob->ended_at, $timezone);
                        $importJob->ended_at = $date;
                    }
                    $importJob->save();
                }

                // journal entries
                foreach ($user->account->journalEntries as $journalEntry) {
                    if (! is_null($journalEntry->date)) {
                        $date = self::convertToUTC($journalEntry->date, $timezone);
                        $journalEntry->date = $date;
                    }
                    $journalEntry->save();
                }

                // notes
                foreach ($user->account->notes as $note) {
                    if (! is_null($note->favorited_at)) {
                        $date = self::convertToUTC($note->favorited_at, $timezone);
                        $note->favorited_at = $date;
                    }
                    $note->save();
                }

                // notifications
                foreach ($user->account->notifications as $notification) {
                    if (! is_null($notification->trigger_date)) {
                        $date = self::convertToUTC($notification->trigger_date, $timezone);
                        $notification->trigger_date = $date;
                    }
                    $notification->save();
                }

                // reminders
                foreach ($user->account->reminders as $reminder) {
                    if (! is_null($reminder->last_triggered)) {
                        $date = self::convertToUTC($reminder->last_triggered, $timezone);
                        $reminder->last_triggered = $date;
                    }
                    if (! is_null($reminder->next_expected_date)) {
                        $date = self::convertToUTC($reminder->next_expected_date, $timezone);
                        $reminder->next_expected_date = $date;
                    }
                    $reminder->save();
                }

                // special dates
                foreach ($user->account->specialDates as $specialDate) {
                    if (! is_null($specialDate->date)) {
                        $date = self::convertToUTC($specialDate->date, $timezone);
                        $specialDate->date = $date;
                    }
                    $specialDate->save();
                }

                // special dates
                foreach ($user->account->tasks as $task) {
                    if (! is_null($task->completed_at)) {
                        $date = self::convertToUTC($task->completed_at, $timezone);
                        $task->completed_at = $date;
                    }
                    $task->save();
                }
            }
        });
    }

    private function convertToUTC($date, $timezone)
    {
        $date = DateHelper::createDateFromFormat($date, $timezone);
        $date->setTimezone('UTC');

        //\Log::info('timezone: '.$date->timezone->getName().' | converted date: '.$date);
        return $date;
    }
}
