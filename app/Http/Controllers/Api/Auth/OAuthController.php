<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Http\Controllers\Api\Auth;

use GuzzleHttp\Client;
use App\Models\User\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Validator;

class OAuthController extends Controller
{
    use JsonRespondController;

    /**
     * Log in a user and returns an accessToken.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // Disable debugger for caldav output
        if (config('app.debug')) {
            Debugbar::disable();
        }

        $isvalid = $this->validateRequest($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $token = $this->proxy([
                'username' => $request->get('email'),
                'password' => $request->get('password'),
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
     * @return mixed
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
        $count = User::where('email', $request->get('email'))
                    ->count();
        if ($count === 0) {
            return $this->respondUnauthorized();
        }

        return true;
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     */
    private function proxy(array $data = [])
    {
        $http = new Client();
        $response = $http->post(route('passport.token'), [
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
