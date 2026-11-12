<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AddSupportMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function create(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $supportRequest)
            ->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'files' => ['nullable', 'array', 'max:'.SupportAttachment::MAXIMUM_PER_MESSAGE],
            'files.*' => ['file', 'max:'.SupportAttachment::MAXIMUM_SIZE_IN_KILOBYTES, 'mimes:'.SupportAttachment::ACCEPTED_TYPES],
        ]);

        $message = new AddSupportMessage(
            user: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
            files: array_values($request->file('files', [])),
        )->execute();

        return new SupportMessageResource($message->load('attachments'))->response()->setStatusCode(201);
    }
}
