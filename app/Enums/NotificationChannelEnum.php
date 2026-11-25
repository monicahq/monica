<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum NotificationChannelEnum: string implements Translatable
{
    case InApp = 'in_app';
    case Email = 'email';
    case Telegram = 'telegram';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'In the app',
            self::Email => 'Email',
            self::Telegram => 'Telegram',
            self::Sms => 'Text message',
        };
    }

    /**
     * The channel named inside a sentence about where something went, lowercase
     * because it never starts one. A locale that capitalises differently says so
     * in its own translation rather than having a case change applied to it.
     */
    public function deliveredPhrase(): string
    {
        return match ($this) {
            self::InApp => 'in the app',
            self::Email => 'by email',
            self::Telegram => 'on Telegram',
            self::Sms => 'by text message',
        };
    }

    public function deliveredSentence(): string
    {
        return match ($this) {
            self::InApp => 'It appeared in the application.',
            self::Email => 'It went out by email.',
            self::Telegram => 'It went out on Telegram.',
            self::Sms => 'It went out by text message.',
        };
    }

    public function needsDestination(): bool
    {
        return match ($this) {
            self::InApp, self::Email => false,
            self::Telegram, self::Sms => true,
        };
    }

    /**
     * Whether the service hands us the destination instead of the account
     * typing it.
     *
     * Telegram does. Nobody knows their own chat identifier, and asking for it
     * would be asking for a number they would have to go and dig out. Instead
     * they open a conversation with the bot, the bot tells us which
     * conversation it is, and there is nothing to mistype and nothing to prove
     * afterwards: an identifier Telegram gave us is one Telegram will accept.
     */
    public function discoversDestination(): bool
    {
        return $this === self::Telegram;
    }

    /**
     * Whether a wrong destination is accepted without complaint. A text message
     * provider takes any well formed number, so nothing comes back to say the
     * number is not yours. Those channels are the ones that need a code typed
     * back before they may be switched on.
     */
    public function provesSilently(): bool
    {
        return $this === self::Sms;
    }

    public function maximumLength(): ?int
    {
        return match ($this) {
            self::Telegram => 4096,
            self::Sms => 140,
            self::InApp, self::Email => null,
        };
    }

    /**
     * The Laravel notification channel that carries this one, or null where the
     * row itself is the delivery or nothing delivers it yet.
     */
    public function laravelChannel(): ?string
    {
        return match ($this) {
            self::Email => 'mail',
            self::Telegram => 'telegram',
            self::Sms => 'vonage',
            self::InApp => null,
        };
    }

    /**
     * Whether whoever runs this server has set up what the channel needs from
     * them.
     *
     * What can be missing is the provider account, which belongs to whoever
     * runs the server. Only Telegram and the text message need one. In app and
     * email ask the instance for nothing beyond what it already has.
     *
     * A channel every server can use answers true and has nothing more to
     * say; the question only bites where the provider account is the
     * instance's.
     */
    public function isConfiguredOnTheInstance(): bool
    {
        return match ($this) {
            self::Sms => filled(config('vonage.api_key'))
                && filled(config('vonage.api_secret'))
                && filled(config('vonage.sms_from')),
            self::Telegram => filled(config('services.telegram.token'))
                && filled(config('services.telegram.bot_url')),
            self::InApp, self::Email => true,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->deliveredPhrase(), $this->deliveredSentence()];
    }
}
