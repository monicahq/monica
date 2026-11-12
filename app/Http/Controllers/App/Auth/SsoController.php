<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\ConnectProviderToUser;
use App\Actions\SignInWithProvider;
use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SsoController extends Controller
{
    /**
     * The session key the security screen sets before sending somebody to their
     * provider, so that the one callback a provider is registered with can tell
     * connecting from signing in.
     */
    public const string CONNECT_INTENT = 'sso.connect';

    /**
     * What the relay puts in front of the name it encrypts, so that the callback
     * accepts no ciphertext but its own, and the relay, which encrypts whatever
     * it is sent, cannot be used to encrypt a value for anything else.
     */
    private const string RELAYED_NAME = 'sso.relay.name|';

    public function redirect(Request $request): SymfonyRedirectResponse
    {
        $request->session()->forget(self::CONNECT_INTENT);

        try {
            return Socialite::driver($this->provider($request)->value)->redirect();
        } catch (Throwable) {
            return $this->refuse(false, __('That did not work. Try again, or sign in another way.'));
        }
    }

    /**
     * What Apple posts, carried into the callback as a visit. The name Apple
     * shares travels encrypted, so it stays out of the address bar and the logs.
     */
    public function relay(Request $request): RedirectResponse
    {
        $fields = array_filter($request->only(['code', 'state', 'error']), is_string(...));
        $name = $request->input('user');

        if (is_string($name) && $name !== '') {
            $fields['user'] = Crypt::encryptString(self::RELAYED_NAME.$name);
        }

        return redirect()->route('auth.sso.callback', ['provider' => $this->provider($request)->value, ...$fields], 303);
    }

    public function callback(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        $relayedName = $request->query('user');
        $request->query->remove('user');

        if (is_string($relayedName)) {
            $decrypted = (string) rescue(fn (): string => Crypt::decryptString($relayedName), '', report: false);
            $request->query->set('user', str_starts_with($decrypted, self::RELAYED_NAME) ? substr($decrypted, strlen(self::RELAYED_NAME)) : null);
        }

        $connecting = $request->session()->pull(self::CONNECT_INTENT) === true;

        try {
            $providerUser = Socialite::driver($provider->value)->user();
        } catch (Throwable) {
            return $this->refuse($connecting, __('That did not work. Try again, or sign in another way.'));
        }

        if ($connecting && Auth::check()) {
            return $this->connect($request, $provider, (string) $providerUser->getId());
        }

        return $this->signIn($request, $provider, $providerUser);
    }

    private function connect(Request $request, SsoProviderEnum $provider, string $ssoId): RedirectResponse
    {
        try {
            new ConnectProviderToUser(
                user: $request->user(),
                provider: $provider,
                ssoId: $ssoId,
            )->execute();
        } catch (ValidationException $exception) {
            return $this->refuse(true, $exception->getMessage());
        }

        return redirect()->route('settings.security.index')
            ->with('status', __(':provider now signs you in.', ['provider' => $provider->brandName()]));
    }

    private function signIn(Request $request, SsoProviderEnum $provider, mixed $providerUser): RedirectResponse
    {
        try {
            $user = new SignInWithProvider(
                provider: $provider,
                providerUser: $providerUser,
                ip: $request->ip(),
            )->execute();
        } catch (ValidationException $exception) {
            return $this->refuse(false, $exception->getMessage());
        }

        if ($user->usesTwoFactorAuthentication()) {
            $request->session()->put('twoFactor.user.id', $user->id);
            $request->session()->put('twoFactor.remember', true);
            $request->session()->put('twoFactor.signInMethod', SignInMethodEnum::Provider->value);
            $request->session()->put('twoFactor.provider', $provider->value);

            return redirect()->route('auth.twoFactor.new');
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home.index'));
    }

    private function refuse(bool $connecting, string $message): RedirectResponse
    {
        if ($connecting) {
            return redirect()->route('settings.security.index')->withErrors(['provider' => $message]);
        }

        return redirect()->route('auth.login.new')->withErrors(['email' => $message]);
    }

    private function provider(Request $request): SsoProviderEnum
    {
        return SsoProviderEnum::from((string) $request->route()->parameter('provider'));
    }
}
