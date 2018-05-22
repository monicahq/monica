<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Notifications\ConfirmEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Http\Requests\EmailChangeRequest;
use Illuminate\Foundation\Auth\RedirectsUsers;

class EmailChangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->session()->has('user_id')) {

            $model = config('auth.providers.users.model');

            $user = $model::findOrFail($request->session()->get('user_id'));

            return view('auth.emailchange')
                ->with('email', $user->email);
        }

        return redirect('/');
    }

    /**
     * Change user email.
     *
     * @param EmailChangeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function save(EmailChangeRequest $request)
    {
        if ($request->session()->has('user_id')) {
            $response = $this->validateAndEmailChange($request);

            return $response == 'auth.email_changed'
                ? $this->sendChangedResponse($response)
                : $this->sendChangedFailedResponse($response);
        }

        return redirect('/');
    }

    /**
     * Validate a password change request and update password of the user.
     *
     * @param EmailChangeRequest $request
     * @return mixed
     */
    protected function validateAndEmailChange(EmailChangeRequest $request)
    {
        $user = $this->validateChange($request);
        if (! $user instanceof Model) {
            return $user;
        }

        // Change email of the user
        $user->email = $request->get('newmail');

        // Resend validation token
        $user->confirmation_code = str_random(30);
        $user->confirmed = false;
        $user->save();
    
        $user->notify(new ConfirmEmail);

        // Logout the user
        Auth::guard()->logout();
        $request->session()->invalidate();

        return 'auth.email_changed';
    }

    /**
     * Validate a password change request with the given credentials.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function validateChange(Request $request)
    {
        if (is_null($user = $this->getUser($request))) {
            return 'passwords.invalid';
        }

        return $user;
    }

    /**
     * Get the user with the given credentials.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Auth\CanResetPassword|null
     */
    protected function getUser(Request $request)
    {
        $model = config('auth.providers.users.model');

        $user = $model::findOrFail($request->session()->get('user_id'));

        // Using current email from user, and current password sent with the request to authenticate the user
        if (! Auth::attempt(['email' => $user->email, 'password' => $request['password']])) {
            // authentication fails
            return;
        }

        return $user;
    }

    /**
     * Get the response for a successful password changed.
     *
     * @param string $response
     * @return \Illuminate\Http\Response
     */
    protected function sendChangedResponse($response)
    {
        return redirect('/')
                    ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password.
     *
     * @param string $response
     * @return \Illuminate\Http\Response
     */
    protected function sendChangedFailedResponse($response)
    {
        return redirect('/')
                    ->withErrors(trans($response));
    }
}
