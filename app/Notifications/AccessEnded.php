<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\AccessEndReasonEnum;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class AccessEnded extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AccessEndReasonEnum $reason,
    ) {
        $this->onQueue('high');
    }

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('Your :app account is back on the free plan', ['app' => config('app.name')]))
            ->greeting(__('Full access has ended'))
            ->line($this->why())
            ->line(__('Nothing was deleted. Everything you wrote is still there, still yours to read and to edit. The free plan only refuses additions once you are at one of its ceilings.'))
            ->action(__('See the plans'), route('settings.billing.index'))
            ->outroLine(__('Full access can be taken up again at any time, by buying it once or by subscribing.'))
            ->panel(__('If this comes as a surprise, reply to this email and we will look at what happened.'));
    }

    private function why(): string
    {
        return match ($this->reason) {
            AccessEndReasonEnum::Refunded => __('The payment for your one time purchase was refunded, so the account is back on the free plan.'),
            AccessEndReasonEnum::Disputed => __('The payment was disputed with your bank, so the account is back on the free plan while that is settled.'),
            AccessEndReasonEnum::Cancelled => __('Your subscription ended today, as you asked, so the account is back on the free plan.'),
            AccessEndReasonEnum::PaymentFailed => __('The payment could not be taken after several attempts, so the subscription ended and the account is back on the free plan.'),
        };
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::AccessEnded;
    }
}
