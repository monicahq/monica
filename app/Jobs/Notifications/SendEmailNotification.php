<?php

namespace App\Jobs\Notifications;

use App\Mail\SendReminder;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
            ->queue(
                (new SendReminder($this->contactReminder, $user))
                ->onQueue('low')
            );

        UserNotificationSent::create([
            'user_notification_channel_id' => $this->userNotificationChannel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->contactReminder->label,
        ]);
    }
}
