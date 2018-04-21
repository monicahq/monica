<?php

namespace App\Jobs\StayInTouch;

use App\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Check if a user can be sent a notification and if that's the case,
 * actually send it. It also indicates how many emails will be necessary to
 * delete the notification.
 */
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

        foreach ($account->users as $user) {
            if ($user->shouldBeReminded($this->contact->stay_in_touch_trigger_date)
                && ! $account->hasLimitations()) {
                dispatch(new SendStayInTouchEmail($this->contact, $user));
            }
        }
    }
}
