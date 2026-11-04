<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MarkFullAccessOfferAsDismissed;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeFullAccessOfferController extends Controller
{
    public function update(Request $request): UserResource
    {
        new MarkFullAccessOfferAsDismissed(
            user: $request->user(),
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
