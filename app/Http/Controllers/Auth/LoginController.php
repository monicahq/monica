<?php

namespace App\Http\Controllers\Auth;

use App\Models\Account\Account;
use App\Http\Controllers\Controller;
use Bestmomo\LaravelEmailConfirmation\Traits\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

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
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function showLoginOrRegister()
    {
        $first = ! Account::hasAny();
        if ($first) {
            return redirect('/register');
        }

        return $this->showLoginForm();
    }
}
