<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\TelegramWebhookRegistration;
use Illuminate\Support\Facades\Hash;

class TelegramWebhook
{
    /**
     * Where Telegram has to send what people say to the bot.
     */
    public static function incomingAddress(): string
    {
        return route('telegram.webhook');
    }

    /**
     * Why the bot cannot be registered right now, or null when it can. The
     * screen shows it beside the control and the action refuses with it, so the
     * two never disagree.
     */
    public static function missingPrerequisite(): ?string
    {
        if (config('services.telegram.installed') !== true) {
            return __('Unavailable on this server, because its package is not installed.');
        }

        if (blank(config('services.telegram.token'))) {
            return __('Set the bot token first.');
        }

        if (blank(config('services.telegram.webhook_secret'))) {
            return __('Set the secret first.');
        }

        if (! self::isReachableFromTheInternet(self::incomingAddress())) {
            return __('Telegram only calls an HTTPS address it can reach from the internet, and :address is not one.', [
                'address' => self::incomingAddress(),
            ]);
        }

        return null;
    }

    /**
     * What changed since the bot was registered, in words the screen can show.
     * Empty when nothing did.
     *
     * @return list<string>
     */
    public static function outOfDateReasons(TelegramWebhookRegistration $registration): array
    {
        $reasons = [];

        if ($registration->url !== self::incomingAddress()) {
            $reasons[] = __('the instance address changed');
        }

        if (! Hash::check((string) config('services.telegram.token'), $registration->token_fingerprint)) {
            $reasons[] = __('the bot token changed');
        }

        if (! Hash::check((string) config('services.telegram.webhook_secret'), $registration->secret_fingerprint)) {
            $reasons[] = __('the secret changed');
        }

        return $reasons;
    }

    public static function isCurrent(?TelegramWebhookRegistration $registration): bool
    {
        return $registration instanceof TelegramWebhookRegistration && self::outOfDateReasons($registration) === [];
    }

    /**
     * Telegram refuses plain HTTP and cannot reach a development host. Private
     * network ranges are left to Telegram's own refusal rather than resolved here.
     */
    private static function isReachableFromTheInternet(string $address): bool
    {
        if (parse_url($address, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($address, PHP_URL_HOST));

        if (in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
            return false;
        }

        return ! str_ends_with($host, '.localhost') && ! str_ends_with($host, '.test');
    }
}
