<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function show(Request $request, Vault $vault, File $file): StreamedResponse
    {
        abort_unless($file->account_id === $request->user()->account_id, 404);
        abort_unless($file->vault_id === $vault->id, 404);

        $disk = $file->disk();

        abort_unless($disk->exists($file->path), 404);

        return $disk->response($file->path, name: $file->original_name, headers: [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ], disposition: $file->contentDisposition());
    }
}
