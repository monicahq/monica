<?php

namespace App\Domains\Contact\ManageDocuments\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\DestroyDocument;
use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Domains\Contact\ManageDocuments\Web\ViewHelpers\ModuleDocumentsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleDocumentController extends Controller
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
            'type' => File::TYPE_DOCUMENT,
        ];

        $file = (new UploadFile())->execute($data);

        $contact = Contact::findOrFail($contactId);

        return response()->json([
            'data' => ModuleDocumentsViewHelper::dto($file, $contact),
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

        (new DestroyDocument())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
