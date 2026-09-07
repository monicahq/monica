<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class MarketplaceListingRejected extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $listingName,
        public readonly int $versionNumber,
        public readonly string $reason,
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
            ->subject(__('About :name in the marketplace', ['name' => $this->listingName]))
            ->greeting(__('Your listing was not accepted'))
            ->line(__('Version :version of :name has not been accepted into the marketplace.', [
                'version' => $this->versionNumber,
                'name' => $this->listingName,
            ]))
            ->panel($this->reason)
            ->outroLine(__('You can work on it and submit a new version whenever you are ready. Nothing you built has been touched, and nothing has been removed from your account.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::MarketplaceListingRejected;
    }
}
