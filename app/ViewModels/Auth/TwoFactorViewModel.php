<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Models\User;

class TwoFactorViewModel extends GuestViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function offersKey(): bool
    {
        if (! PasskeyAvailability::onThisInstance()) {
            return false;
        }

        return $this->user->securityKeys()->exists() || $this->user->hasPasskeys();
    }

    public function usesAuthenticatorApp(): bool
    {
        return $this->user->usesAuthenticatorApp();
    }

    public function description(): string
    {
        if ($this->offersKey() && $this->usesAuthenticatorApp()) {
            return __('Use your security key or passkey when your browser asks, or type the code your authenticator app is showing.');
        }

        if ($this->offersKey()) {
            return __('Use your security key or passkey when your browser asks.');
        }

        if (! $this->usesAuthenticatorApp()) {
            return __('Security keys need a secure connection. Type one of your recovery codes instead.');
        }

        if ($this->user->securityKeys()->exists()) {
            return __('Security keys need a secure connection. Open your authenticator app and type the code it is showing.');
        }

        return __('Open your authenticator app and type the code it is showing.');
    }

    public function codeLabel(): string
    {
        if ($this->usesAuthenticatorApp()) {
            return __('Six digit code');
        }

        return __('Recovery code');
    }

    public function notice(): string
    {
        if ($this->usesAuthenticatorApp()) {
            return __('Lost your phone? Use one of the recovery codes you saved when you set this up. Each one works once.');
        }

        return __('Lost your security key? Use one of the recovery codes you saved when you added it. Each one works once.');
    }

    /** @return array{redirectUrl: string, openOnLoad: bool, routes: array{options: string, submit: string}, text: array<string, string>} */
    public function keyPrompt(bool $openOnLoad): array
    {
        return [
            'redirectUrl' => route('home.index'),
            'openOnLoad' => $openOnLoad,
            'routes' => [
                'options' => route('auth.twoFactorKeyOptions.show'),
                'submit' => route('auth.twoFactor.create'),
            ],
            'text' => [
                'opening' => __('Your browser is asking for your security key or passkey.'),
                'waiting' => __('Press the button to use your security key or passkey.'),
                'cancelled' => __('Nothing was confirmed. The prompt was dismissed.'),
                'alreadyRegistered' => __('That key cannot be used here.'),
                'failed' => __('That did not work. Try again, or use another way below.'),
                'tooMany' => __('Too many tries. Wait a minute and start again.'),
            ],
        ];
    }
}
