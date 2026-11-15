<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\AccessKindEnum;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Override;

class AccessUnlocked extends Notification implements HasEmailType, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public readonly AccessKindEnum $kind,
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
            ->subject(__('Your :app account now has full access', ['app' => config('app.name')]))
            ->greeting(__('Full access is yours'))
            ->line($this->what())
            ->line(__('Nothing is capped any more: as many vaults and as many contacts as you need.'))
            ->action(__('See your billing'), route('settings.billing.index'))
            ->outroLine($this->whatComesNext())
            ->panel(__('The receipt for this payment comes from Stripe, who took it. Every invoice is on your billing screen too.'));
    }

    private function what(): string
    {
        return match ($this->kind) {
            AccessKindEnum::Lifetime => __('Your payment went through, and full access is yours for good. There is nothing further to pay and nothing to renew.'),
            AccessKindEnum::Subscription => __('Your subscription has started, and full access is yours for as long as it runs.'),
            AccessKindEnum::Granted => __('We have opened full access on your account.'),
        };
    }

    private function whatComesNext(): string
    {
        return match ($this->kind) {
            AccessKindEnum::Lifetime => __('Keep this email: it is the record of the day you bought it.'),
            AccessKindEnum::Subscription => __('Your billing screen says when it next renews, and stopping it is one click away.'),
            AccessKindEnum::Granted => __('There is nothing to pay and nothing to renew.'),
        };
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::AccessUnlocked;
    }
}
