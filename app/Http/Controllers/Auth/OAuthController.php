<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Http\Controllers\Controller;
use App\Auth\Exceptions\InvalidCredentialsException;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Application;


class OAuthController extends Controller
{
    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * Log in a user and returns an accessToken
     */
    public function login(LoginRequest $request)
    {
        if(\Antiflood::checkIp(5) === FALSE) {
            return response()->json(['code' => 'too-many-attemps'], 403);
        }
        
        $email = $request->get('email');
        $password = $request->get('password');
        
        $count = DB::table('users')->where('email', $email)->count();
        if ($count === 0) {
            return $this->handleError();
        }

        try {
            return response()->json($this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]));
        } catch(Exception $e) {
            return $this->handleError();
        }
    }

    private function handleError() {
        \Antiflood::putIp(5);
        return response()->json(null, 403);
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     */
    private function proxy($grantType, array $data = [])
    {
        $data = array_merge($data, [
            'client_id'     => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'grant_type'    => $grantType
        ]);
        

        $response = $this->app->make('apiconsumer')->post('/oauth/token', $data);

        if (!$response->isSuccessful()) {
            throw new Exception();
        }

        $data = json_decode($response->getContent());

        return [
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in,
        ];
    }
}

