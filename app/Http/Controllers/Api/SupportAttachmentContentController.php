<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportAttachmentContentController extends Controller
{
    public function show(Request $request, string $supportRequest, string $attachment): StreamedResponse
    {
        $supportRequest = SupportRequest::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $supportRequest)
            ->firstOrFail();

        $attachment = SupportAttachment::query()
            ->where('account_id', $supportRequest->account_id)
            ->whereIn('support_message_id', $supportRequest->visibleMessages()->select('id'))
            ->where('uuid', $attachment)
            ->firstOrFail();

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
