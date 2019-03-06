<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Helpers\RequestHelper;
use App\Jobs\SendNewUserAlert;
use App\Models\Account\Account;
use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

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
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $first = ! Account::hasAny();
        if (config('monica.disable_signup') == 'true' && ! $first) {
            abort(403, trans('auth.signup_disabled'));
        }

        return view('auth.register')
            ->withFirst($first)
            ->withLocales(CollectionHelper::sortByCollator(LocaleHelper::getLocaleList(), 'lang'));
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
            'policy' => 'required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return User
     */
    protected function create(array $data)
    {
        $first = ! Account::hasAny();
        $account = Account::createDefault(
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password'],
            RequestHelper::ip(),
            $data['lang']
        );
        $user = $account->users()->first();

        if (! $first) {
            // send me an alert
            dispatch(new SendNewUserAlert($user));
        }

        return $user;
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $first = Account::count() == 1;
        if (! config('monica.signup_double_optin') || $first) {
            // if signup_double_optin is disabled, skip the confirm email part
            $user->markEmailAsVerified();

            $this->guard()->login($user);

            return redirect()->route('login');
        }
    }
}
