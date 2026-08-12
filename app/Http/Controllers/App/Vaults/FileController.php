<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hand back one file somebody put on a record.
 *
 * Files live on the private disk and are read through here rather than from a
 * public URL, because a document about somebody is as private as the record it
 * sits on: a link that worked without signing in would be a way out of the
 * vault, and the vault is the whole point.
 *
 * The address names the vault as well as the file, and both are checked. The
 * vault is resolved within the account of whoever is asking, and the file has to
 * be one kept in that vault, so neither half of the address can be leaned on
 * alone.
 *
 * It answers with the bytes under the name somebody uploaded, which is not the
 * name on the disk: what is stored is a uuid, so that nothing about a file can be
 * guessed from a path and nothing somebody typed ends up in a filesystem.
 */
class FileController extends Controller
{
    public function show(Request $request, Vault $vault, File $file): StreamedResponse
    {
        abort_unless($file->account_id === $request->user()->account_id, 404);
        abort_unless($file->vault_id === $vault->id, 404);

        $disk = $file->disk();

        abort_unless($disk->exists($file->path), 404);

        /*
         * Shown rather than downloaded, so a photograph opens as a photograph
         * and a pdf opens in the reader the browser already has. The name is
         * still carried, so saving it keeps it.
         */
        return $disk->response($file->path, name: $file->original_name, headers: [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
