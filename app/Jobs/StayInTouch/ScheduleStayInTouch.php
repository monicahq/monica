<?php

namespace App\Jobs\StayInTouch;

use App\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
        $mailSent = false;

        foreach ($account->users as $user) {
            if ($user->shouldBeReminded($this->contact->stay_in_touch_trigger_date)
                && ! $account->hasLimitations()) {
                $this->contact->sendStayInTouchEmail($user);
                $mailSent = true;
                $timezone = $user->timezone;
            }
        }

        if ($mailSent) {
            $this->contact->setStayInTouchTriggerDate($this->contact->stay_in_touch_frequency, $timezone);
        }
    }
}
