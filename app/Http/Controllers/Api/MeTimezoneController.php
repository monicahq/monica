<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTimezone;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeTimezoneController extends Controller
{
    public function update(Request $request): UserResource
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'timezone:all'],
        ]);

        new UpdateTimezone(
            user: $request->user(),
            timezone: $validated['timezone'],
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
