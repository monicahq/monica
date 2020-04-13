<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Helpers\RequestHelper;
use App\Jobs\SendNewUserAlert;
use App\Services\User\CreateUser;
use App\Models\Account\Invitation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RedirectsUsers;

class InvitationController extends Controller
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
     * Display the specified resource.
     *
     * @param string $key
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function show($key)
    {
        if (Auth::check()) {
            return redirect()->route('loginRedirect');
        }

        $invitation = Invitation::where('invitation_key', $key)
            ->firstOrFail();

        return view('settings.users.accept')
            ->withKey($key)
            ->withEmail($invitation->email);
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
            'email_security' => 'required',
            'password' => 'required|min:6|confirmed',
            'policy' => 'required',
        ]);
    }

    /**
     * Store the specified resource.
     *
     * @param Request $request
     * @param string $key
     *
     * @return null|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $key)
    {
        $this->validator($request->all())->validate();

        $invitation = Invitation::where('invitation_key', $key)
                                    ->firstOrFail();

        // as a security measure, make sure that the new user provides the email
        // of the person who has invited him/her.
        if ($request->input('email_security') != $invitation->invitedBy->email) {
            return redirect()->back()->withErrors(trans('settings.users_error_email_not_similar'))->withInput();
        }

        event(new Registered($user = $this->create($request->all(), $invitation)));

        $invitation->delete();

        Auth::guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @param  mixed $invitation
     * @return \App\Models\User\User
     */
    protected function create(array $data, $invitation)
    {
        $user = app(CreateUser::class)->execute([
            'account_id' => $invitation->account_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $invitation->invitedBy->locale,
            'ip_address' => RequestHelper::ip(),
        ]);
        $user->invited_by_user_id = $invitation->invited_by_user_id;
        $user->save();

        // send me an alert
        dispatch(new SendNewUserAlert($user));

        return $user;
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     */
    protected function registered(Request $request, $user)
    {
        if (! config('monica.signup_double_optin')) {
            // if signup_double_optin is disabled, skip the confirm email part
            $user->markEmailAsVerified();
        }
    }
}
