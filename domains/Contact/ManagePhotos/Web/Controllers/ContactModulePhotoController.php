<?php

namespace App\Contact\ManagePhotos\Web\Controllers;

use App\Contact\ManageDocuments\Services\UploadFile;
use App\Contact\ManagePhotos\Services\DestroyPhoto;
use App\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModulePhotoController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'uuid' => $request->input('uuid'),
            'name' => $request->input('name'),
            'original_url' => $request->input('original_url'),
            'cdn_url' => $request->input('cdn_url'),
            'mime_type' => $request->input('mime_type'),
            'size' => $request->input('size'),
            'type' => File::TYPE_PHOTO,
        ];

        $file = (new UploadFile())->execute($data);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModulePhotosViewHelper::dto($file, $contact),
        ], 201);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $fileId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'file_id' => $fileId,
        ];

        (new DestroyPhoto())->execute($data);

        return response()->json([
            'data' => route('contact.photo.index', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
