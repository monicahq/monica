<?php

namespace App\Http\Controllers\Settings;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use Illuminate\Foundation\Auth\RedirectsUsers;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class MultiFAController extends Controller
{
    use RedirectsUsers, JsonRespondController;

    /**
     * @var string
     */
    protected $redirectTo = '/settings/security';

    /**
     * Session var name to store secret code.
     *
     * @var string
     */
    private $SESSION_TFA_SECRET = '2FA_secret';

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enableTwoFactor(Request $request)
    {
        //generate new secret
        $secret = $this->generateSecret();

        $user = $request->user();

        //generate image for QR barcode
        $imageDataUri = Google2FA::getQRCodeInline(
            $request->getHttpHost(),
            $user->email,
            $secret,
            200
        );

        $request->session()->put($this->SESSION_TFA_SECRET, $secret);

        return response()->json(['image' => $imageDataUri, 'secret' => $secret]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateTwoFactor(Request $request)
    {
        //get user
        $user = $request->user();

        if (! is_null($user->google2fa_secret)) {
            return response()->json(['error' => trans('settings.2fa_enable_error_already_set')]);
        }

        $this->validate($request, [
            'one_time_password' => 'required',
        ]);

        //retrieve secret
        $secret = $request->session()->pull($this->SESSION_TFA_SECRET);

        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->verifyGoogle2FA($secret, $request['one_time_password'])) {
            //encrypt and then save secret
            $user->google2fa_secret = $secret;
            $user->save();

            $authenticator->login();

            return response()->json(['success' => true]);
        }

        $authenticator->logout();

        return response()->json(['success' => false]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function disableTwoFactor(Request $request)
    {
        // @phpstan-ignore-next-line
        return view('settings.security.2fa-disable');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivateTwoFactor(Request $request)
    {
        $this->validate($request, [
            'one_time_password' => 'required',
        ]);

        $user = $request->user();

        if ($this->validateTwoFactorLogin($request, $user, $request['one_time_password'])) {
            //make secret column blank
            $user->google2fa_secret = null;
            $user->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Validate 2nd factor for user with 2FA code or recovery code.
     *
     * @param  Request  $request
     * @param  User  $user
     * @param  string  $oneTimePassword
     * @return bool
     */
    private function validateTwoFactorLogin(Request $request, User $user, string $oneTimePassword): bool
    {
        //retrieve secret
        $secret = $user->google2fa_secret;

        $authenticator = app(Authenticator::class)->boot($request);

        // try provided token as a 2FA code, or as a recovery code
        if ($authenticator->verifyGoogle2FA($secret, $oneTimePassword)
            || $user->recoveryChallenge($oneTimePassword)) {
            $authenticator->logout();

            return true;
        }

        return false;
    }

    /**
     * Generate a secret key in Base32 format.
     *
     * @return string
     */
    private function generateSecret()
    {
        return Google2FA::generateSecretKey(32);
    }
}
