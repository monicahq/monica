<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SolveSupportRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupportSolutionController extends Controller
{
    public function create(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $supportRequest)
            ->firstOrFail();

        try {
            new SolveSupportRequest(user: $request->user(), supportRequest: $supportRequest)->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return new SupportRequestResource($supportRequest)->response()->setStatusCode(200);
    }
}
