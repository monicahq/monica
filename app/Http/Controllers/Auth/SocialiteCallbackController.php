<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AttemptToAuthenticateSocialite;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Socialite\Facades\Socialite;

class SocialiteCallbackController extends Controller
{
    /**
     * Handle socalite login.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login(Request $request, string $driver)
    {
        $this->checkProvider($driver);

        $redirect = $request->input('redirect');
        if ($redirect && Str::of($redirect)->startsWith($request->getSchemeAndHttpHost())) {
            Redirect::setIntendedUrl($redirect);
        }

        if ($request->boolean('remember')) {
            $request->session()->put('login.remember', true);
        }

        return Inertia::location(Socialite::driver($driver)->redirect());
    }

    /**
     * Handle socalite callback.
     */
    public function callback(Request $request, string $driver): RedirectResponse
    {
        try {
            $this->checkProvider($driver);
            $this->checkForErrors($request, $driver);

            return $this->loginPipeline($request)->then(function (/* $request */) {
                return Redirect::intended(route('home'));
            });
        } catch (ValidationException $e) {
            throw $e->redirectTo(Redirect::intended(route('home'))->getTargetUrl());
        }
    }

    /**
     * Get the authentication pipeline instance.
     */
    protected function loginPipeline(Request $request): Pipeline
    {
        return (new Pipeline(app()))->send($request)->through([
            AttemptToAuthenticateSocialite::class,
            PrepareAuthenticatedSession::class,
        ]);
    }

    /**
     * Check for errors.
     */
    private function checkForErrors(Request $request, string $driver): void
    {
        if ($request->filled('error')) {
            throw ValidationException::withMessages([
                $driver => [$request->input('error_description')],
            ]);
        }
    }

    /**
     * Check if the driver is activated.
     */
    private function checkProvider(string $driver): void
    {
        if (! collect(config('auth.login_providers'))->contains($driver)) {
            throw ValidationException::withMessages([
                $driver => ['This provider does not exist.'],
            ]);
        }
    }
}
