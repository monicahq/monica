<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Events\RecoveryLogin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RedirectsUsers;

class RecoveryLoginController extends Controller
{
    use RedirectsUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        return view('auth.recovery.login');
    }

    /**
     * Validate recovery login.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'recovery' => 'required',
        ])->validate();

        $user = auth()->user();
        $recovery = $request->get('recovery');

        if ($this->recoveryLogin($user, $recovery)) {
            $this->fireLoginEvent($user);
        } else {
            abort(403);
        }

        return redirect($this->redirectPath());
    }

    /**
     * Try login with the recovery code.
     *
     * @param \App\Models\User\User  $user
     * @param string  $recovery
     * @return bool
     */
    protected function recoveryLogin(User $user, string $recovery)
    {
        $recoveryCodes = $user->recoveryCodes()
                                ->where('used', false)
                                ->get();

        foreach ($recoveryCodes as $recoveryCode) {
            if ($recoveryCode->recovery == $recovery) {
                $recoveryCode->forceFill([
                    'used' => true,
                ])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Fire the login event.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireLoginEvent($user)
    {
        Event::dispatch(new RecoveryLogin($user));
    }
}
