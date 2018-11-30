<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

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
            'email' => 'required|email',
            'recovery' => 'required',
        ])->validate();

        $user = User::where('email', $request->get('email'));

        // Check if email exists. If not respond with an Unauthorized, this way a hacker
        // doesn't know if the login email exist or not, or if the recovery code was wrong
        if ($user->count() === 0) {
            abort(403);
        }
        $user = $user->first();

        $recoveryCodes = $user->recoveryCodes()
                                ->where('used', false)
                                ->get();

        $success = false;
        foreach ($recoveryCodes as $recoveryCode) {
            if ($recoveryCode->recovery == $request->get('recovery')) {
                $success = true;
                $recoveryCode->forceFill([
                    'used' => true,
                ])->save();
                break;
            }
        }

        if (! $success) {
            abort(403);
        }

        // login
        Auth::guard()->login($user, true);

        return redirect($this->redirectPath());
    }
}
