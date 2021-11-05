<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Events\RecoveryLogin;
use App\Http\Controllers\Controller;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function get(Request $request)
    {
        return view('auth.recovery.login');
    }

    /**
     * Validate recovery login.
     *
     * @param  Request  $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'recovery' => 'required',
        ])->validate();

        $user = auth()->user();
        $recovery = $request->input('recovery');

        if ($user instanceof \App\Models\User\User &&
            $user->recoveryChallenge($recovery)) {
            $this->fireLoginEvent($user);
        } else {
            abort(403);
        }

        return redirect($this->redirectPath());
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
