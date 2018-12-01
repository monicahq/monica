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


namespace App\Http\Controllers\Api\Account;

use App\Models\User\User;
use Illuminate\Http\Request;
use App\Models\Settings\Term;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Account\User\User as UserResource;

class ApiUserController extends ApiController
{
    /**
     * Get the detail of the authenticated user.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return new UserResource(auth()->user());
    }

    /**
     * Get the state of a specific term for the user.
     *
     * @param Request $request
     * @param int $termId
     * @return void
     */
    public function get(Request $request, $termId)
    {
        $userCompliance = auth()->user()->getStatusForCompliance($termId);

        if (! $userCompliance) {
            return $this->respondNotFound();
        }

        return $this->respond([
            'data' => $userCompliance,
        ]);
    }

    /**
     * Get all the policies ever signed by the authenticated user.
     *
     * @param Request $request
     * @return void
     */
    public function compliance(Request $request)
    {
        $terms = auth()->user()->getAllCompliances();

        return $this->respond([
            'data' => $terms,
        ]);
    }

    /**
     * Sign the latest policy for the authenticated user.
     *
     * @param Request $request
     * @return void
     */
    public function set(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        // Create the contact
        try {
            $term = auth()->user()->acceptPolicy($request->get('ip_address'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        $userCompliance = auth()->user()->getStatusForCompliance($term->id);

        return $this->respond([
            'data' => $userCompliance,
        ]);
    }
}
