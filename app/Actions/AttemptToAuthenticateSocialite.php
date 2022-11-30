<?php

namespace App\Actions;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\LoginRateLimiter;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\One\User as OAuth1User;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as OAuth2User;
use LaravelWebauthn\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class AttemptToAuthenticateSocialite
{
    /**
     * Create a new action instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  \Laravel\Fortify\LoginRateLimiter  $limiter
     * @return void
     */
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter
    ) {
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        $driver = $request->route()->parameter('driver');

        $provider = $this->getSocialiteProvider($driver);
        $user = $this->authenticateUser($request, $driver, $provider->user());

        if ((optional($user)->two_factor_secret && ! is_null(optional($user)->two_factor_confirmed_at))
            || Webauthn::enabled($user)) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        $this->guard->login($user, $request->session()->pull('login.remember', false));

        return $next($request);
    }

    /**
     * Get the provider.
     *
     * @param  string  $driver
     * @return \Laravel\Socialite\Contracts\Provider
     */
    private function getSocialiteProvider(string $driver): Provider
    {
        $provider = Socialite::driver($driver);

        if (App::environment('local') && $provider instanceof AbstractProvider) {
            $provider->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
        }

        return $provider;
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $driver
     * @param  \Laravel\Socialite\Contracts\User  $socialite
     * @return User
     */
    private function authenticateUser(Request $request, string $driver, SocialiteUser $socialite): User
    {
        if ($userToken = UserToken::where([
            'driver_id' => $socialite->getId(),
            'driver' => $driver,
        ])->first()) {
            // Association already exist

            $user = $userToken->user;

            $this->checkUserAssociation($request, $user, $driver);
        } else {
            // New association: create user or add token to existing user
            $user = tap($this->getUserOrCreate($socialite), function ($user) use ($driver, $socialite) {
                $this->createUserToken($user, $driver, $socialite);
            });
        }

        return $user;
    }

    /**
     * Check if the user is logged in and if the user is the same as the one.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  string  $driver
     * @return void
     */
    private function checkUserAssociation(Request $request, User $user, string $driver): void
    {
        if (($userId = Auth::id()) && $userId !== $user->id) {
            $this->throwFailedAuthenticationException($request, $driver, __('This provider is already associated with another account'));
        }
    }

    /**
     * Get authenticated user.
     *
     * @param  SocialiteUser  $socialite
     * @return User
     */
    private function getUserOrCreate(SocialiteUser $socialite): User
    {
        if ($user = Auth::user()) {
            return $user;
        }

        return $this->createUser($socialite);
    }

    /**
     * Create new user.
     *
     * @param  SocialiteUser  $socialite
     * @return User
     */
    private function createUser(SocialiteUser $socialite): User
    {
        $names = Str::of($socialite->getName())->split('/ /', 2);
        $data = [
            'email' => $socialite->getEmail(),
            'first_name' => $names[0],
            'last_name' => $names[1] ?? $names[0],
            'locale' => App::getLocale(),
            'terms' => true,
        ];

        event(new Registered($user = app(CreateNewUser::class)->create($data)));

        return $user;
    }

    /**
     * Create the user token register.
     *
     * @param  User  $user
     * @param  string  $driver
     * @param  SocialiteUser  $socialite
     * @return UserToken
     */
    private function createUserToken(User $user, string $driver, SocialiteUser $socialite): UserToken
    {
        $token = [
            'driver' => $driver,
            'driver_id' => $socialite->getId(),
            'user_id' => $user->id,
            'email' => $socialite->getEmail(),
        ];

        if ($socialite instanceof OAuth1User) {
            $token['token'] = $socialite->token;
            $token['token_secret'] = $socialite->tokenSecret;
            $token['format'] = 'oauth1';
        } elseif ($socialite instanceof OAuth2User) {
            $token['token'] = $socialite->token;
            $token['refresh_token'] = $socialite->refreshToken;
            $token['expires_in'] = $socialite->expiresIn;
            $token['format'] = 'oauth2';
        } else {
            throw new \UnexpectedValueException('authentication format not supported');
        }

        return UserToken::create($token);
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $driver
     * @param  string  $message
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request, string $driver, string $message = null): void
    {
        $this->fireFailedEvent($request, Auth::user());

        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            $driver => [empty($message) ? trans('auth.failed') : $message],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User|null  $user
     * @return void
     */
    protected function fireFailedEvent(Request $request, ?User $user = null): void
    {
        event(new Failed('web', $user, [
            'email' => $request->email,
        ]));
    }

    /**
     * Get the two factor authentication enabled response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User|null  $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function twoFactorChallengeResponse(Request $request, ?User $user): Response
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->session()->pull('login.remember', $request->filled('remember')),
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);

        return $request->wantsJson()
                    ? response()->json(['two_factor' => true])
                    : redirect()->route('two-factor.login');
    }
}
