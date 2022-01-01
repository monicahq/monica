<?php

namespace App\Listeners;

use App\Models\User\User;
use Illuminate\Auth\Recaller;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\PasswordReset;

class LogoutUserDevices
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\PasswordReset  $event
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        if ($event->user instanceof User) {
            $this->logoutOtherDevices($event->user);

            $this->deleteOtherSessionRecords($event->user);
        }
    }

    /**
     * Invalidate other sessions for the current user.
     *
     * The application must be using the AuthenticateSession middleware.
     *
     * @param  User  $user
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function logoutOtherDevices($user)
    {
        $guard = $this->guard();
        $cookieJar = $guard->getCookieJar();

        if ($this->recaller($guard) ||
            $cookieJar->hasQueued($guard->getRecallerName())) {
            $cookieJar->queue($cookieJar->forever($guard->getRecallerName(),
                $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.$user->getAuthPassword()
            ));
        }
    }

    /**
     * Get the guard.
     *
     * @return SessionGuard
     */
    protected function guard(): SessionGuard
    {
        $guard = Auth::guard('web');
        if (! $guard instanceof SessionGuard) {
            throw new \LogicException('guard is not a SessionGuard kind');
        }

        return $guard;
    }

    /**
     * Get the decrypted recaller cookie for the request.
     *
     * @param  SessionGuard  $guard
     * @return \Illuminate\Auth\Recaller|null
     */
    protected function recaller($guard): ?Recaller
    {
        if ($recaller = request()->cookies->get($guard->getRecallerName())) {
            return new Recaller($recaller);
        }

        return null;
    }

    /**
     * Delete the other browser session records from storage.
     *
     * @param  User  $user
     * @return void
     */
    protected function deleteOtherSessionRecords($user)
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }
}
