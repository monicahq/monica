<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Lahaxearnaud\U2f\Models\U2fKey;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Event;
use Lahaxearnaud\U2f\U2fFacade as U2f;
use Illuminate\Foundation\Auth\RedirectsUsers;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use App\Http\Resources\Settings\U2fKey\U2fKey as U2fKeyResource;

class MultiFAController extends Controller
{
    use RedirectsUsers, JsonRespondController;

    protected $redirectTo = '/settings/security';

    /**
     * Session var name to store secret code.
     */
    private $SESSION_TFA_SECRET = '2FA_secret';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function enableTwoFactor(Request $request)
    {
        //generate new secret
        $secret = $this->generateSecret();

        $user = $request->user();

        //generate image for QR barcode
        $imageDataUri = app('pragmarx.google2fa')->getQRCodeInline(
            $request->getHttpHost(),
            $user->email,
            $secret,
            200
        );

        $request->session()->put($this->SESSION_TFA_SECRET, $secret);

        return response()->json(['image' => $imageDataUri, 'secret' => $secret]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function disableTwoFactor(Request $request)
    {
        return view('settings.security.2fa-disable');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function deactivateTwoFactor(Request $request)
    {
        $this->validate($request, [
            'one_time_password' => 'required',
        ]);

        $user = $request->user();

        //retrieve secret
        $secret = $user->google2fa_secret;

        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->verifyGoogle2FA($secret, $request['one_time_password'])) {

            //make secret column blank
            $user->google2fa_secret = null;
            $user->save();

            $authenticator->logout();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Generate a secret key in Base32 format.
     *
     * @return string
     */
    private function generateSecret()
    {
        $google2fa = app('pragmarx.google2fa');

        return $google2fa->generateSecretKey(32);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function u2fRegisterData(Request $request)
    {
        list($req, $sigs) = app('u2f')->getRegisterData($request->user());
        session(['u2f.registerData' => $req]);

        return $this->respond([
            'currentKeys' => $sigs,
            'registerData' => $req,
        ]);
    }

    public function u2fRegister(Request $request)
    {
        try {
            $key = U2f::doRegister(Auth::user(), session('u2f.registerData'), json_decode($request->input('register')));
            if ($request->filled('name')) {
                $key->name = $request->input('name');
                $key->save();
            }

            Event::fire('u2f.register', ['u2fKey' => $key, 'user' => Auth::user()]);
            session()->forget('u2f.registerData');

            session([config('u2f.sessionU2fName') => true]);

            return new U2fKeyResource($key);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }
    }

    /**
     * Remove an existing security key.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function u2fRemove(Request $request, int $u2fKeyId)
    {
        $u2fKey = U2fKey::where('user_id', auth()->id())
            ->findOrFail($u2fKeyId);

        $u2fKey->delete();

        return $this->respondObjectDeleted($u2fKeyId);
    }
}
