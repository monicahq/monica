<?php

namespace App\Jobs;

use App\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogReminderSent implements ShouldQueue
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
        // log reminder to reminders_sent
        $reminderSent = new ReminderSent;
        $reminderSent->account_id = $this->reminder->account->id;
        $reminderSent->contact_id = $this->reminder->contact->id;
        $reminderSent->reminder_id = $this->reminder->id;
        $reminderSent->title = $this->reminder->account->id;
        $reminderSent->sent_date = \Carbon\Carbon::now();
        $reminderSent->save();
    }
}
