<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The notification settings screen: every channel, the state it is in, and what
 * is left to do before it can carry anything.
 *
 * The steps are read off the channel rather than written down per service, so a
 * channel added later gets its steps for free.
 */
class NotificationChannelsViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channels' => $this->channels(),
            'working_count' => $this->workingCount(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function channels(): Collection
    {
        return $this->rows()->map(fn (NotificationChannel $row): array => $this->shapeOf($row));
    }

    /** @return array<string, mixed> */
    private function shapeOf(NotificationChannel $row): array
    {
        return [
            'channel' => $row->channel->value,
            'label' => __($row->channel->label()),
            'is_enabled' => $row->is_enabled,
            'is_configured_on_the_instance' => $row->channel->isConfiguredOnTheInstance(),
            'destination' => $row->destination,
            'needs_destination' => $row->channel->needsDestination(),
            'discovers_destination' => $row->channel->discoversDestination(),
            'is_connected' => $row->destination !== null,
            'requires_proof' => $row->channel->provesSilently(),
            'is_proven' => $row->isProven(),
            'is_ready' => $row->isReady(),
            'awaits_proof' => $row->channel->provesSilently() && $row->isSetUp() && ! $row->isProven(),
            'disabled_reason' => $row->disabled_reason,
            'state' => $this->state($row),
            'icon' => $this->icon($row->channel),
            'tagline' => $this->tagline($row->channel),
            'summary' => $this->summary($row->channel),
            'carries' => $this->carries(),
            'preview' => $this->preview($row->channel),
            'steps' => $this->steps($row),
            'destination_label' => $this->destinationLabel($row->channel),
            'update_url' => route('settings.notifications.update', $row->channel->value),
            'proof_url' => route('settings.notifications.proof.store', $row->channel->value),
            'proof_confirm_url' => route('settings.notifications.proof.update', $row->channel->value),
            'test_url' => route('settings.notifications.test.store', $row->channel->value),
            'connection_url' => route('settings.notifications.connection.store', $row->channel->value),
            'reset_url' => route('settings.notifications.reset', $row->channel->value),
        ];
    }

    public function smsNotice(): ?string
    {
        $sms = NotificationChannel::query()
            ->where('account_id', $this->user->account_id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->first();

        if ($sms === null || $sms->isOffered() || $sms->destination === null) {
            return null;
        }

        return __('Text messages are unavailable on this account for now. Your number is kept.');
    }

    public function workingCount(): int
    {
        return $this->rows()->filter(fn (NotificationChannel $row): bool => $row->mayCarry())->count();
    }

    /** @return Collection<int, NotificationChannel> */
    private function rows(): Collection
    {
        $rows = NotificationChannel::query()
            ->where('account_id', $this->user->account_id)
            ->get()
            ->keyBy(fn (NotificationChannel $row): string => $row->channel->value);

        return collect(NotificationChannelEnum::cases())
            ->map(fn (NotificationChannelEnum $channel): ?NotificationChannel => $rows->get($channel->value))
            ->filter(fn (?NotificationChannel $row): bool => $row?->isOffered() === true)
            ->values();
    }

    private function state(NotificationChannel $row): string
    {
        if (! $row->channel->isConfiguredOnTheInstance()) {
            return __('Not available on this server');
        }

        if ($row->is_enabled) {
            return __('On');
        }

        if ($row->disabled_reason !== null) {
            return __('Switched off after it stopped working');
        }

        if ($row->channel->discoversDestination() && $row->destination === null) {
            return __('Not connected');
        }

        if (! $row->isSetUp()) {
            return __('Not set up');
        }

        if (! $row->isProven()) {
            return __('Waiting for the code');
        }

        return __('Off');
    }

    private function icon(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::InApp => 'device',
            NotificationChannelEnum::Email => 'mail',
            NotificationChannelEnum::Telegram => 'text',
            NotificationChannelEnum::Sms => 'phone',
        };
    }

    private function tagline(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::InApp => __('A card in the corner of the screen while you are using the application.'),
            NotificationChannelEnum::Email => __('Sent to :destination, one email per notification.', ['destination' => $this->user->email]),
            NotificationChannelEnum::Telegram => __('A message in the conversation you opened with our bot.'),
            NotificationChannelEnum::Sms => __('A text message to the number you gave us.'),
        };
    }

    private function summary(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::InApp => __('A card slides in at the bottom right of whatever page you are on, and stays until you dismiss it.'),
            NotificationChannelEnum::Email => __('Every notification is sent as its own email, however many there are on a given day. Nothing is grouped or held back.'),
            NotificationChannelEnum::Telegram => __('Every notification arrives as its own message, in the conversation and nowhere else.'),
            NotificationChannelEnum::Sms => __('Every notification is sent as one text message, short enough to arrive in one piece.'),
        };
    }

    /**
     * What the application has to say, whichever channel carries it.
     *
     * @return list<array{icon: string, label: string}>
     */
    private function carries(): array
    {
        return [
            ['icon' => 'reminder', 'label' => __('Reminders falling due')],
            ['icon' => 'calendar', 'label' => __('The daily summary, when you ask for one')],
        ];
    }

    /**
     * The shape the preview is drawn in. Telegram and the text message are both
     * a conversation somebody reads on a telephone, so they share one.
     */
    private function preview(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::InApp => 'window',
            NotificationChannelEnum::Email => 'inbox',
            NotificationChannelEnum::Telegram, NotificationChannelEnum::Sms => 'conversation',
        };
    }

    /** @return list<string> */
    private function steps(NotificationChannel $row): array
    {
        if (! $row->channel->isConfiguredOnTheInstance()) {
            return [__('Nothing for you to do. Whoever runs this server has to set up a provider before this channel can carry anything.')];
        }

        $steps = [];

        if ($row->channel->discoversDestination()) {
            $steps[] = __('Nothing to type. Following the link opens a conversation with our bot, and the bot tells us where to send.');
        }

        if ($row->channel->needsDestination() && ! $row->channel->discoversDestination()) {
            $steps[] = $this->destinationStep($row->channel);
        }

        if ($row->channel->provesSilently()) {
            $steps[] = __('Ask for a code, then type it back here. Until you do, nothing will be sent.');
        }

        if ($steps === []) {
            $steps[] = __('Nothing to set up. Switch it on and it works.');
        }

        return $steps;
    }

    private function destinationStep(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::Sms => __('Write the number to send to, with its country code.'),
            default => __('Write where this channel should send.'),
        };
    }

    private function destinationLabel(NotificationChannelEnum $channel): string
    {
        return match ($channel) {
            NotificationChannelEnum::Telegram => __('Conversation'),
            NotificationChannelEnum::Sms => __('Phone number'),
            default => __('Destination'),
        };
    }
}
