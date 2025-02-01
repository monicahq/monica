<?php

namespace App\Domains\Contact\ManagePhotos\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\DestroyFile;
use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModulePhotoController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
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

        $contact = Contact::where('vault_id', $vaultId)->findOrFail($contactId);

        $contact->files()->save($file);

        return response()->json([
            'data' => ModulePhotosViewHelper::dto($file, $contact),
        ], 201);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $fileId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'file_id' => $fileId,
        ];

        (new DestroyFile)->execute($data);

        return response()->json([
            'data' => route('contact.photo.index', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
