<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Notifications\ConfirmEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\EmailChangeRequest;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class EmailChangeController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/settings/emailchange2';

    /**
     * Show the application's login form.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function showLoginFormSpecial(Request $request)
    {
        if ($request->session()->has('user_id')) {
            $user = User::findOrFail($request->session()->get('user_id'));

            return view('auth.emailchange1')
                ->with('email', $user->email);
        }

        return redirect('/');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return view('auth.emailchange2')
            ->with('email', $user->email);
    }

    /**
     * Change user email.
     *
     * @param EmailChangeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function save(EmailChangeRequest $request)
    {
        $response = $this->validateAndEmailChange($request);

        return $response == 'auth.email_changed'
            ? $this->sendChangedResponse($response)
            : $this->sendChangedFailedResponse($response);
    }

    /**
     * Validate a password change request and update password of the user.
     *
     * @param EmailChangeRequest $request
     * @return mixed
     */
    protected function validateAndEmailChange(EmailChangeRequest $request)
    {
        $user = auth()->user();

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
