<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DisconnectProviderFromUser;
use App\Enums\SsoProviderEnum;
use App\Http\Controllers\App\Auth\SsoController as AuthSsoController;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SsoController extends Controller
{
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        $provider = SsoProviderEnum::from((string) $request->route()->parameter('provider'));

        $request->session()->put(AuthSsoController::CONNECT_INTENT, true);

        return Socialite::driver($provider->value)->redirect();
    }

    public function destroy(Request $request): RedirectResponse
    {
        try {
            new DisconnectProviderFromUser(
                user: $request->user(),
                provider: SsoProviderEnum::from((string) $request->route()->parameter('provider')),
            )->execute();
        } catch (ValidationException $exception) {
            return redirect()->route('settings.security.index')
                ->withErrors(['provider' => $exception->getMessage()]);
        }

        return redirect()->route('settings.security.index')
            ->with('status', __('That provider no longer signs you in.'));
    }
}
