<?php

namespace App\Jobs\Notifications;

use Carbon\Carbon;
use App\Mail\SendReminder;
use Illuminate\Bus\Queueable;
use App\Models\UserNotificationSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledContactReminder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ScheduledContactReminder $scheduledReminder;

    /**
     * Create a new job instance.
     *
     * @param  ScheduledContactReminder  $scheduledReminder
     * @return void
     */
    public function __construct(ScheduledContactReminder $scheduledReminder)
    {
        $this->scheduledReminder = $scheduledReminder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emailAddress = $this->scheduledReminder->userNotificationChannel->content;
        $user = $this->scheduledReminder->userNotificationChannel->user;

        Mail::to($emailAddress)
            ->queue((new SendReminder($this->scheduledReminder, $user))
                ->onQueue('low')
            );

        $this->scheduledReminder->triggered_at = Carbon::now();
        $this->scheduledReminder->save();

        UserNotificationSent::create([
            'user_notification_channel_id' => $this->scheduledReminder->userNotificationChannel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->scheduledReminder->reminder->label,
        ]);
    }
}
