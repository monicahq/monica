<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Reminder;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SetNextReminderDate implements ShouldQueue
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
        switch ($this->reminder->frequency_type) {
            case 'one_time':
                $this->reminder->delete();
                break;

            default:
                $this->reminder->last_triggered = $this->reminder->next_expected_date;
                $this->reminder->calculateNextExpectedDate();
                $this->reminder->save();

                $this->reminder->purgeNotifications();
                $this->reminder->scheduleNotifications();
                break;
        }
    }
}
