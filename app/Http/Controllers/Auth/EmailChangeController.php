<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Services\User\EmailChange;
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showLoginFormSpecial(Request $request)
    {
        $user = $request->user();
        if ($user &&
            $user instanceof User &&
            ! $user->hasVerifiedEmail()) {
            return view('auth.emailchange1')
                ->with('email', $user->email);
        }

        return redirect()->route('login');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $user = auth()->user();

        return view('auth.emailchange2')
            ->with('email', $user->email);
    }

    /**
     * Change user email.
     *
     * @param EmailChangeRequest $request
     * @return \Illuminate\Http\RedirectResponse
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
        $user = $request->user();

        app(EmailChange::class)->execute([
            'account_id' => $user->account_id,
            'email' => $request->input('newmail'),
            'user_id' => $user->id,
        ]);

        // Logout the user
        Auth::guard()->logout();
        $request->session()->invalidate();

        return 'auth.email_changed';
    }

    /**
     * Get the response for a successful password changed.
     *
     * @param string $response
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedResponse($response)
    {
        return redirect()->route('login')
                    ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password.
     *
     * @param string $response
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedFailedResponse($response)
    {
        return redirect()->route('login')
                    ->withErrors(trans($response));
    }
}
