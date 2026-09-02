<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTimezone;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * The time zone of the user the token belongs to.
 *
 * A name only. The map's other shape, a position to resolve, belongs to a
 * screen with a map on it: a caller writing a script already knows which zone
 * it means, and a coordinate would be an odd way to say so.
 */
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
