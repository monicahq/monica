<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User\UserToken;
use Illuminate\Auth\AuthManager;

class AuthenticateToken
{
    /**
     * The guard factory instance.
     *
     * @var AuthManager
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  AuthManager  $auth
     * @return void
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate($request);

        return $next($request);
    }

    /**
     * Handle authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    private function authenticate($request)
    {
        if ($this->auth->guard()->check()) {
            return;
        }

        $guard = $this->auth->guard('token');

        if ($user = $guard->user()) {
            $token = UserToken::find($user->token_id);

            if ($request->is($token->dav_resource)) {
                $this->auth->guard()->setUser($user);
            }
        }
    }
}
