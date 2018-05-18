<?php

namespace App\Http\Controllers\Api\Account;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Account\User\User as UserResource;

class ApiUserController extends ApiController
{
    /**
     * Get the detail of the authenticated user.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return new UserResource(auth()->user());
    }

    public function get(Request $request, $termId)
    {
        // $term = User::whereHas('terms', function (query) use ($termId) {
        //     $query->whereIn('user.id', auth()->user()->id)
        //             ->whereIn('term.id', $termId);
        // })->first();

        return $this->respond([
            'data' => [
                'signed' => 'yes',
                'user' => new UserResource(auth()->user),
                'term' => new TermResource($term),
            ],
        ]);
    }
}
