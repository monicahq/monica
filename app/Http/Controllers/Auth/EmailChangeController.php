<?php

namespace App\Http\Controllers\Auth;

use \Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\EmailChangeRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class EmailChangeController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/confirmation/resend';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['guest', 'startsession']);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('auth.emailchange');
    }

    /**
     * Change user email.
     *
     * @param EmailChangeRequest $request
     */
    public function save(EmailChangeRequest $request)
    {
        $this->validateLogin($request);

        $response = $this->validateAndEmailChange($request);

        return $response == 'email.changed'
            ? redirect($this->redirectPath())
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
        $user = $this->validateChange($request);
        if (! $user instanceof Authenticatable) {
            return $user;
        }

        $user->update(['email' => $request['newmail']]);

        return 'email.changed';
    }

    /**
     * Validate a password change request with the given credentials.
     *
     * @param \Illuminate\Http\Request $credentials
     * @return mixed
     *
     * @throws \UnexpectedValueException
     */
    protected function validateChange(Request $credentials)
    {
        return $this->getUser($credentials);
    }

    /**
     * Get the user with the given credentials.
     *
     * @param \Illuminate\Http\Request $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword|null
     */
    protected function getUser(Request $credentials)
    {
        return $this->login($credentials);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        return Auth::user();
    }

    /**
     * Get the response for a failed password changed.
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
