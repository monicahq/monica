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

namespace App\Jobs\StayInTouch;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;
use App\Notifications\StayInTouchEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ScheduleStayInTouch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $account = $this->contact->account;

        $users = [];
        foreach ($account->users as $user) {
            if ($user->isTheRightTimeToBeReminded($this->contact->stay_in_touch_trigger_date)
                && ! $account->hasLimitations()) {
                array_push($users, $user);
            }
        }

        if (count($users) > 0) {
            NotificationFacade::send($users, new StayInTouchEmail($this->contact));
            $this->contact->setStayInTouchTriggerDate($this->contact->stay_in_touch_frequency);
        }
    }
}
