<?php

namespace App\Domains\Contact\ManageAvatar\Web\Controllers;

use App\Domains\Contact\ManageAvatar\Services\DestroyAvatar;
use App\Domains\Contact\ManageAvatar\Services\UpdatePhotoAsAvatar;
use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleAvatarController extends Controller
{
    public function update(Request $request, string $vaultId, string $contactId)
    {
        // first we upload the file
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
            'type' => File::TYPE_AVATAR,
        ];

        $file = (new UploadFile)->execute($data);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'file_id' => $file->id,
        ];

        (new UpdatePhotoAsAvatar)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        (new DestroyAvatar)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
