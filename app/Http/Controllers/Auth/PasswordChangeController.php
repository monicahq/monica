<?php

namespace App\Http\Controllers\Auth;

use App\Models\User\User;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordChangeRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Contracts\Auth\CanResetPassword;

class PasswordChangeController extends Controller
{
    use RedirectsUsers;

    protected $redirectTo = '/settings/security';

    /**
     * Get useful parameters from request.
     *
     * @param \App\Http\Requests\PasswordChangeRequest $request
     * @return array
     */
    protected function credentials(PasswordChangeRequest $request)
    {
        return $request->only(
            'password_current', 'password', 'password_confirmation'
        );
    }

    /**
     * Change user password.
     *
     * @param \App\Http\Requests\PasswordChangeRequest $request
     */
    public function passwordChange(PasswordChangeRequest $request)
    {
        $credentials = $this->credentials($request);

        $response = $this->validateAndPasswordChange($credentials);

        return $response == 'passwords.changed'
            ? $this->sendChangedResponse($response)
            : $this->sendChangedFailedResponse($response);
    }

    /**
     * Validate a password change request and update password of the user.
     *
     * @param array $credentials
     *
     * @return string|Authenticatable
     */
    protected function validateAndPasswordChange($credentials)
    {
        $user = $this->validateChange($credentials);
        if (! $user instanceof CanResetPassword) {
            return $user;
        }

        if ($user instanceof User) {
            $this->setNewPassword($user, $credentials['password']);
        }

        return 'passwords.changed';
    }

    /**
     * Validate a password change request with the given credentials.
     *
     * @param array $credentials
     *
     * @return string|Authenticatable
     *
     * @throws \UnexpectedValueException
     */
    protected function validateChange(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return 'passwords.invalid';
        }

        return $user;
    }

    /**
     * Get the user with the given credentials.
     *
     * @param array $credentials
     *
     * @return null|Authenticatable
     */
    protected function getUser(array $credentials): ?Authenticatable
    {
        /** @var User */
        $user = Auth::user();

        // Using current email from user, and current password sent with the request to authenticate the user
        if (! Auth::attempt([
            'email' => $user->getEmailForPasswordReset(),
            'password' => $credentials['password_current'],
        ])) {
            // authentication fails
            return null;
        }

        return $user;
    }

    /**
     * Set the new password if all validation has passed.
     *
     * @param User $user
     * @param string $password
     * @return void
     */
    protected function setNewPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        Auth::guard()->login($user);
    }

    /**
     * Get the response for a successful password change.
     *
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedResponse($response)
    {
        return redirect($this->redirectPath())
                    ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password change.
     *
     * @param string $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendChangedFailedResponse($response)
    {
        return redirect($this->redirectPath())
                    ->withErrors(['password' => trans($response)]);
    }
}
