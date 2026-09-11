<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Enums\NotificationChannelEnum;
use App\Helpers\MessageShortener;
use App\Models\User;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * The same message, built for whichever of the outbound services is carrying
 * it.
 *
 * Every notification that can go out on more than one channel builds these the
 * same way, from one plain sentence the notification supplies and the limit the
 * channel declares. Writing them per notification would mean four copies that
 * drift, and a fifth channel would have to be added to all of them.
 *
 * Where a channel's credential comes from is the channel's own business.
 * No channel carries a credential of the account's own. Telegram and the text
 * message both run through a provider account belonging to the instance, so
 * the client and the token come from the configuration the way Laravel builds
 * them.
 */
trait BuildsMessagesForAnyChannel
{
    public function toTelegram(User $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            ->content($this->shortened(NotificationChannelEnum::Telegram));
    }

    public function toVonage(User $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->clientReference((string) $notifiable->id)
            ->content($this->shortened(NotificationChannelEnum::Sms));
    }

    /**
     * The one sentence this notification says, with no formatting of any kind,
     * for the channels that carry nothing else.
     */
    abstract protected function plainText(): string;

    private function shortened(NotificationChannelEnum $channel): string
    {
        return MessageShortener::to($this->plainText(), $channel->maximumLength());
    }
}
