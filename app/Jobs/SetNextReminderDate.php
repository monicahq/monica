<?php

namespace App\Jobs;

use App\User;
use App\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SetNextReminderDate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reminder;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder, User $user)
    {
        $this->reminder = $reminder;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->reminder->frequency_type == 'one_time') {
            $this->reminder->delete();
        } else {
            $frequencyType = $this->reminder->frequency_type;
            $frequencyNumber = $this->reminder->frequency_number;
            $startDate = $this->reminder->next_expected_date;
            $this->reminder->calculateNextExpectedDate($startDate, $frequencyType, $frequencyNumber);
            $this->reminder->save();
        }
    }
}
