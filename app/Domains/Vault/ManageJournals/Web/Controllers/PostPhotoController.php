<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\DestroyFile;
use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Domains\Vault\ManageJournals\Services\AddPhotoToPost;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostPhotoController extends Controller
{
    public function store(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'uuid' => $request->input('uuid'),
            'name' => $request->input('name'),
            'original_url' => $request->input('original_url'),
            'cdn_url' => $request->input('cdn_url'),
            'mime_type' => $request->input('mime_type'),
            'size' => $request->input('size'),
            'type' => File::TYPE_PHOTO,
        ];

        $file = (new UploadFile)->execute($data);

        $post = (new AddPhotoToPost)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'file_id' => $file->id,
        ]);

        return response()->json([
            'data' => PostEditViewHelper::dtoPhoto($post->journal, $post, $file),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $postId, int $fileId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'file_id' => $fileId,
        ];

        (new DestroyFile)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
