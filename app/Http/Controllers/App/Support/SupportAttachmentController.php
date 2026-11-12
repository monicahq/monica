<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportAttachmentController extends Controller
{
    public function show(Request $request, SupportAttachment $supportAttachment): StreamedResponse
    {
        $disk = Storage::disk($supportAttachment->disk);

        abort_unless($disk->exists($supportAttachment->path), 404);

        return $disk->download($supportAttachment->path, $supportAttachment->original_name, [
            'Content-Type' => $supportAttachment->mime_type,
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
