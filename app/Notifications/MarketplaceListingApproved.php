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

/**
 * Tell an author their version made it into the catalogue.
 *
 * The listing name and the version number are handed in rather than the version
 * itself, so the email can be rendered long after a queue has carried it and
 * nothing has to be reloaded to send it.
 */
class MarketplaceListingApproved extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $listingName,
        public readonly int $versionNumber,
        public readonly string $url,
    ) {
        $this->onQueue('high');
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__(':name is in the marketplace', ['name' => $this->listingName]))
            ->greeting(__('It is in'))
            ->line(__('Version :version of :name has been approved and is now in the marketplace. Anybody can install it from today.', [
                'version' => $this->versionNumber,
                'name' => $this->listingName,
            ]))
            ->action(__('See it in the marketplace'), $this->url)
            ->line(__('What people install is frozen at the version they install. When you publish a new one, everybody holding an older copy is told and decides for themselves whether to move.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::MarketplaceListingApproved;
    }
}
