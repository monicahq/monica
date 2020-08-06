<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User\User;
use Illuminate\Http\Request;
use function Safe\json_decode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\JsonRespondController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Response;

class OAuthController extends Controller
{
    use JsonRespondController;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;

        if (config('app.debug')) {
            Debugbar::disable();
        }
    }

    /**
     * Display a log in form for oauth accessToken.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $request->session()->flush();

        return view('auth.oauthlogin');
    }

    /**
     * Log in a user and returns an accessToken.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function login(Request $request): ?Response
    {
        $isvalid = $this->validateRequest($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        $email = $request->input('email');
        $password = $request->input('password');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            // The user is active, not suspended, and exists.

            $request->session()->put('oauth', true);
            $request->session()->put('email', $email);
            $request->session()->put('password', $this->encrypter->encrypt($password));

            $this->fixRequest($request);

            // add intendedUrl for WebAuthn
            Redirect::setIntendedUrl(route('oauth.verify'));

            return Route::respondWithRoute('oauth.verify');
        }

        return null;
    }

    /**
     * Fix request parameters.
     *
     * @param Request $request
     * @return void
     */
    private function fixRequest(Request $request)
    {
        $request->setMethod('GET');
        $cookie = $request->cookies->get(config('session.cookie'));
        $request->cookies->set(config('session.cookie'), $this->encrypter->encrypt($cookie));
    }

    /**
     * Validate the request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|true
     */
    private function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        // Check if email exists. If not respond with an Unauthorized, this way a hacker
        // doesn't know if the login email exist or not, or if the password is wrong
        $count = User::where('email', $request->input('email'))->count();
        if ($count === 0) {
            return $this->respondUnauthorized();
        }

        return true;
    }

    /**
     * Log in a user and returns an accessToken.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $response = $this->handleVerify($request);

        Auth::logout();
        $request->session()->flush();

        return $response ?: $this->respondUnauthorized();
    }

    /**
     * Handle the verify request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function handleVerify(Request $request): ?JsonResponse
    {
        if (! $request->session()->has('email') || ! $request->session()->has('password')) {
            return null;
        }

        $request->query->set('email', $request->session()->pull('email'));
        $request->query->set('password', $this->encrypter->decrypt($request->session()->pull('password')));

        $isvalid = $this->validateRequest($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $token = $this->proxy([
                'username' => $request->input('email'),
                'password' => $request->input('password'),
                'grantType' => 'password',
            ]);

            return $this->respond($token);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param array $data the data to send to the server
     *
     * @return array
     * @throws \Safe\Exceptions\JsonException
     */
    private function proxy(array $data = []): array
    {
        $url = App::runningUnitTests() ? config('app.url').'/oauth/token' : route('passport.token');
        /** @var \Illuminate\Http\Response */
        $response = app(Kernel::class)->handle(Request::create($url, 'POST', [
            'grant_type' => $data['grantType'],
            'client_id' => config('passport.personal_access_client.id'),
            'client_secret' => config('passport.personal_access_client.secret'),
            'username' => $data['username'],
            'password' => $data['password'],
            'scope' => '',
        ]));

        $data = json_decode($response->content());

        return [
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in,
        ];
    }
}
