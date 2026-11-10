<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupFileContentController extends Controller
{
    public function show(Request $request, string $vault, string $group, string $file): StreamedResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $group = Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $group)
            ->firstOrFail();

        $stored = GroupFile::query()
            ->where('group_id', $group->id)
            ->where('uuid', $file)
            ->with('file')
            ->firstOrFail()
            ->file;

        $disk = $stored->disk();

        abort_unless($disk->exists($stored->path), 404);

        return $disk->response($stored->path, name: $stored->original_name, headers: [
            'Content-Type' => $stored->mime_type,
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ], disposition: $stored->contentDisposition());
    }
}
