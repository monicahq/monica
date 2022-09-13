<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\WallpaperHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use LaravelWebauthn\Facades\Webauthn;

class LoginController extends Controller
{
    /**
     * Display the login view.
     *
     * @param  Request  $request
     * @return \Inertia\Response
     */
    public function __invoke(Request $request): Response
    {
        $providers = collect(config('auth.login_providers'))->reject(fn ($provider) => empty($provider));
        $providersName = $providers->mapWithKeys(fn ($provider) => [
            $provider => config("services.$provider.name") ?? __("auth.login_provider_{$provider}"),
        ]);

        $data = [];

        if ($webauthnRemember = $request->cookie('webauthn_remember')) {
            if (($user = User::find($webauthnRemember)) && $user->webauthnKeys()->count() > 0) {
                $data['publicKey'] = Webauthn::prepareAssertion($user);
                $data['userName'] = $user->name;
            } else {
                Cookie::expire('webauthn_remember');
            }
        }

        return Inertia::render('Auth/Login', $data + [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'wallpaperUrl' => WallpaperHelper::getRandomWallpaper(),
            'providers' => $providers,
            'providersName' => $providersName,
        ]);
    }
}
