<?php

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

            return;
        }

        $now = now();
        while ($this->contact->stay_in_touch_trigger_date < $now) {
            // If stay in touch was missed, we reschedule it.
            $this->contact->setStayInTouchTriggerDate($this->contact->stay_in_touch_frequency, $this->contact->stay_in_touch_trigger_date);
        }
    }
}
