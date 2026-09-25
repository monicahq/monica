<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MarkUserAsWelcomed;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeWelcomeController extends Controller
{
    public function update(Request $request): UserResource
    {
        new MarkUserAsWelcomed(
            user: $request->user(),
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
