<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Display the register view.
     */
    public function __invoke(Request $request): Response
    {
        $providers = collect(config('auth.login_providers'))
            ->filter(fn ($provider) => ! empty($provider))
            ->mapWithKeys(fn ($provider) => [
                $provider => [
                    'name' => config("services.$provider.name") ?? trans_ignore("auth.login_provider_{$provider}"),
                    'logo' => config("services.$provider.logo") ?? "/img/auth/$provider.svg",
                ],
            ]);

        return Inertia::render('Auth/Register', [
            'providers' => $providers,
            'beta' => $request->cookie('beta') !== 'false',
        ]);
    }
}
