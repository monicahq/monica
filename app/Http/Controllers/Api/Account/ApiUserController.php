<?php

namespace App\Http\Controllers\Api\Account;

use App\User;
use Illuminate\Http\Request;
use App\Models\Settings\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Account\User\User as UserResource;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;

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

        if ($userCompliance == false) {
            return $this->respondNotFound();
        }

        return $this->respond([
            'data' => $userCompliance]
        );
    }

    /**
     * Get all the policies ever signed by the authenticated user.
     *
     * @param Request $request
     * @return void
     */
    public function compliance(Request $request)
    {

    }
}
