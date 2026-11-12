<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SendSupportReply;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportReplyController extends Controller
{
    public function create(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['nullable', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        $reply = new SendSupportReply(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
            statusAfter: SupportRequestStatusEnum::tryFrom((string) ($validated['status'] ?? '')) ?? SupportRequestStatusEnum::WaitingOnUser,
        )->execute();

        return new SupportMessageResource($reply->load('attachments'))->response()->setStatusCode(201);
    }
}
