<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\InstanceSettingEnum;
use App\Helpers\TelegramWebhook;
use App\Models\TelegramWebhookRegistration;

/**
 * The Telegram block of the configuration screen: whether the bot can be
 * registered, where Telegram would be told to call, and whether the last
 * registration still holds.
 */
class TelegramRegistrationPanel
{
    public function unavailableReason(): ?string
    {
        return TelegramWebhook::missingPrerequisite();
    }

    /**
     * Asking Telegram needs only the token, so the check is offered before the
     * bot can be registered.
     */
    public function canBeChecked(): bool
    {
        return config('services.telegram.installed') === true && filled(config('services.telegram.token'));
    }

    public function canGenerateSecret(): bool
    {
        return config('services.telegram.installed') === true;
    }

    /**
     * What generating a secret would break, or null when it breaks nothing:
     * only a secret the current registration relies on is worth a warning.
     */
    public function secretReplacementWarning(): ?string
    {
        if (blank(config('services.telegram.webhook_secret')) || ! TelegramWebhook::isCurrent($this->registration())) {
            return null;
        }

        return __(InstanceSettingEnum::TelegramBotWebhookSecret->warning() ?? '');
    }

    public function address(): string
    {
        return TelegramWebhook::incomingAddress();
    }

    public function registration(): ?TelegramWebhookRegistration
    {
        return once(fn (): ?TelegramWebhookRegistration => TelegramWebhookRegistration::query()->first());
    }

    /**
     * One of `unregistered`, `outOfDate` or `registered`. Whether the control
     * can be used is a separate question, answered by the unavailable reason.
     */
    public function state(): string
    {
        $registration = $this->registration();

        if (! $registration instanceof TelegramWebhookRegistration) {
            return 'unregistered';
        }

        return TelegramWebhook::isCurrent($registration) ? 'registered' : 'outOfDate';
    }

    public function statusSentence(): string
    {
        $registration = $this->registration();

        if (! $registration instanceof TelegramWebhookRegistration) {
            return __('The bot is not registered with Telegram yet.');
        }

        $date = $registration->registered_at->isoFormat('LL');

        if ($this->state() === 'outOfDate') {
            return __('Registered on :date, but out of date because :reasons. Register it again.', [
                'date' => $date,
                'reasons' => implode(', ', TelegramWebhook::outOfDateReasons($registration)),
            ]);
        }

        return __('Registered on :date for :address.', ['date' => $date, 'address' => $registration->url]);
    }
}
