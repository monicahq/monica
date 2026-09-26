<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Helpers\TelegramWebhook;
use App\Jobs\LogAdministratorAction;
use App\Models\TelegramWebhookRegistration;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Tell Telegram where this instance receives what people say to the bot, and
 * remember having done it, so a conversation can be connected to an account.
 * The screen and the console both run it; from the console nobody is signed in.
 */
class RegisterTelegramWebhook
{
    private TelegramWebhookRegistration $registration;

    /**
     * The address Telegram gives for the bot when it differs from the one set
     * here, so the screen can point the difference out. Null when they agree or
     * when Telegram did not say.
     */
    public ?string $differingBotAddress = null;

    public function __construct(
        private readonly ?User $administrator,
    ) {}

    public function execute(): TelegramWebhookRegistration
    {
        $this->validate();
        $this->register();
        $this->record();
        $this->log();
        $this->fillBotAddress();

        return $this->registration;
    }

    private function validate(): void
    {
        $reason = TelegramWebhook::missingPrerequisite();

        if ($reason !== null) {
            throw ValidationException::withMessages(['telegram' => $reason]);
        }
    }

    private function register(): void
    {
        try {
            $response = $this->telegram()->post('setWebhook', [
                'url' => TelegramWebhook::incomingAddress(),
                'secret_token' => config('services.telegram.webhook_secret'),
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'telegram' => __('Telegram could not be reached. Try again in a moment.'),
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'telegram' => __('Telegram refused it: :reason', [
                    'reason' => $response->json('description', $response->body()),
                ]),
            ]);
        }
    }

    private function record(): void
    {
        $this->registration = TelegramWebhookRegistration::query()->first() ?? new TelegramWebhookRegistration;
        $this->registration->user_id = $this->administrator?->id;
        $this->registration->url = TelegramWebhook::incomingAddress();
        $this->registration->token_fingerprint = Hash::make((string) config('services.telegram.token'));
        $this->registration->secret_fingerprint = Hash::make((string) config('services.telegram.webhook_secret'));
        $this->registration->registered_at = now();
        $this->registration->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::TelegramWebhookRegistered,
            parameters: ['url' => $this->registration->url],
            subject: $this->registration,
        )->onQueue('low');
    }

    /**
     * The bot address people are sent to is read from Telegram rather than typed
     * by hand. A failure here leaves the registration standing: the address can
     * still be set on the screen.
     */
    private function fillBotAddress(): void
    {
        try {
            $response = $this->telegram()->get('getMe');
        } catch (ConnectionException) {
            return;
        }

        $username = $response->json('result.username');

        if ($response->failed() || ! is_string($username) || $username === '') {
            return;
        }

        $reported = "https://t.me/{$username}";
        $current = config('services.telegram.bot_url');

        if (blank($current)) {
            new UpdateInstanceSetting(
                administrator: $this->administrator,
                setting: InstanceSettingEnum::TelegramBotUrl,
                value: $reported,
            )->execute();

            return;
        }

        if ($current !== $reported) {
            $this->differingBotAddress = $reported;
        }
    }

    /**
     * Both calls together stay under the fifteen seconds an administrator is
     * promised an answer in, even when Telegram does not answer at all.
     */
    private function telegram(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot'.config('services.telegram.token'))
            ->connectTimeout(3)
            ->timeout(6);
    }
}
