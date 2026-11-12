<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ChangeSupportRequestStatus;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SupportRequestResource;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportStatusController extends Controller
{
    public function update(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        $supportRequest = new ChangeSupportRequestStatus(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            status: SupportRequestStatusEnum::from($validated['status']),
        )->execute();

        return new SupportRequestResource($supportRequest)->response()->setStatusCode(200);
    }
}
