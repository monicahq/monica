<?php

namespace App\Http\Controllers\Api\Account;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
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
        $term = User::whereHas('terms', function (query) use ($termId) {
            $query->whereIn('user.id', auth()->user()->id)
                    ->whereIn('term.id', $termId);
        })->first();

        return $this->respond([
            'data' => [
                'signed' => 'yes',
                'user' => new UserResource(auth()->user()),
                'term' => new ComplianceResource($term),
            ],
        ]);
    }
}
