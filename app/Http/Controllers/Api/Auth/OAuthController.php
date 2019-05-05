<?php

namespace App\Http\Controllers\Api\Auth;

use GuzzleHttp\Client;
use App\Models\User\User;
use Illuminate\Http\Request;
use function Safe\json_decode;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Route;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Validator;

class OAuthController extends Controller
{
    use JsonRespondController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
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
        return view('auth.oauthlogin');
    }

    /**
     * Log in a user and returns an accessToken.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
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
            $request->session()->put('password', encrypt($password));

            return Route::respondWithRoute('oauth.verify');
        }
    }

    /**
     * Log in a user and returns an accessToken.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->query->set('email', $request->session()->pull('email'));
        $request->query->set('password', decrypt($request->session()->pull('password')));

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
            return $this->respondUnauthorized();
        }
    }

    /**
     * Validate the request.
     *
     * @param  Request $request
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
        // doesn't know if the login email exist or not, or if the password os wrong
        $count = User::where('email', $request->input('email'))
                    ->count();
        if ($count === 0) {
            return $this->respondUnauthorized();
        }

        return true;
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param array $data the data to send to the server
     * @return array
     */
    private function proxy(array $data = [])
    {
        $http = new Client([
            'timeout' => 20,
        ]);
        $url = App::runningUnitTests() ? config('app.url').'/oauth/token' : route('passport.token');
        $response = $http->post($url, [
            'form_params' => [
                'grant_type' => $data['grantType'],
                'client_id' => config('monica.mobile_client_id'),
                'client_secret' => config('monica.mobile_client_secret'),
                'username' => $data['username'],
                'password' => $data['password'],
                'scope' => '',
            ],
        ]);

        $data = json_decode($response->getBody());

        return [
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in,
        ];
    }
}
