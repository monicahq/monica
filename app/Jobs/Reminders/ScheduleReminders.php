<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Jobs\Reminders;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Reminder;
use App\Jobs\SetNextReminderDate;
use Illuminate\Queue\SerializesModels;
use App\Notifications\UserRemindedMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ScheduleReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reminder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $account = $this->reminder->contact->account;

        $users = [];
        foreach ($account->users as $user) {
            if ($user->isTheRightTimeToBeReminded($this->reminder->next_expected_date)
                && ! $account->hasLimitations()) {
                array_push($users, $user);
            }
        }

        if (count($users) > 0) {
            NotificationFacade::send($users, new UserRemindedMail($this->reminder));
        }
        dispatch(new SetNextReminderDate($this->reminder));
    }
}
