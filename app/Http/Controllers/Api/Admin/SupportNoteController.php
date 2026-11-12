<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\AddSupportNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportNoteController extends Controller
{
    public function create(Request $request, string $supportRequest): JsonResponse
    {
        $supportRequest = SupportRequest::query()->where('uuid', $supportRequest)->firstOrFail();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $note = new AddSupportNote(
            administrator: $request->user(),
            supportRequest: $supportRequest,
            body: $validated['body'],
        )->execute();

        return new SupportMessageResource($note->load('attachments'))->response()->setStatusCode(201);
    }
}
