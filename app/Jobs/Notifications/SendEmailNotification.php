<?php

namespace App\Jobs\Notifications;

use Carbon\Carbon;
use App\Mail\SendReminder;
use Illuminate\Bus\Queueable;
use App\Models\ContactReminder;
use App\Models\UserNotificationSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Models\UserNotificationChannel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public UserNotificationChannel $userNotificationChannel;
    public ContactReminder $contactReminder;

    /**
     * Create a new job instance.
     *
     * @param  int  $userNotificationChannelId
     * @param  int  $contactReminderId
     * @return void
     */
    public function __construct(int $userNotificationChannelId, int $contactReminderId)
    {
        $this->userNotificationChannel = UserNotificationChannel::findOrFail($userNotificationChannelId);
        $this->contactReminder = ContactReminder::findOrFail($contactReminderId);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emailAddress = $this->userNotificationChannel->content;
        $user = $this->userNotificationChannel->user;

        Mail::to($emailAddress)
            ->queue((new SendReminder($this->contactReminder, $user))
                ->onQueue('low')
            );

        UserNotificationSent::create([
            'user_notification_channel_id' => $this->userNotificationChannel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->contactReminder->label,
        ]);
    }
}
