<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Ircop\Antiflood\Facade\Antiflood;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Validator;

class OAuthController extends Controller
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Log in a user and returns an accessToken.
     */
    public function login(Request $request)
    {
        if (Antiflood::checkIp(5) === false) {
            return $this->handleError(true);
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->handleError();
        }

        $email = $request->get('email');
        $password = $request->get('password');

        $count = User::where('email', $email)->count();
        if ($count === 0) {
            return $this->handleError();
        }

        try {
            return response()->json($this->proxy([
                'username' => $email,
                'password' => $password,
                'grantType' => 'password',
            ]));
        } catch (Exception $e) {
            return $this->handleError();
        }
    }

    /**
     * Handle any error that might have occured.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleError($putIp = false, $errorCode = 42)
    {
        if ($putIp) {
            Antiflood::putIp(5);
        }

        return response()->json([
            'error' => [
                'message' => config('api.error_codes.'.$errorCode),
                'error_code' => $errorCode,
            ],
        ], 403);
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     */
    private function proxy(array $data = [])
    {
        $http = new \GuzzleHttp\Client;
        $response = $http->post(config('app.url').'/oauth/token', [
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
