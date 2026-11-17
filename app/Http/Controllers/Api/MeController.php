<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(Request $request): UserResource
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        new UpdateUserProfile(
            user: $request->user(),
            firstName: $validated['first_name'] ?? $request->user()->first_name,
            lastName: $validated['last_name'] ?? $request->user()->last_name,
            email: $request->user()->email,
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
