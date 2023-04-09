<?php

namespace App\Domains\Contact\ManagePhotos\Web\Controllers;

use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ContactPhotosIndexViewHelper;
use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ContactPhotosShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactPhotoController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        $files = File::where('ufileable_id', $contactId)
            ->where('type', File::TYPE_PHOTO)
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return Inertia::render('Vault/Contact/Photos/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactPhotosIndexViewHelper::data($files, $contact),
            'paginator' => PaginatorHelper::getData($files),
        ]);
    }

    public function show(Request $request, string $vaultId, string $contactId, int $photoId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        $photo = File::where('ufileable_id', $contactId)
            ->where('type', File::TYPE_PHOTO)
            ->findOrFail($photoId);

        return Inertia::render('Vault/Contact/Photos/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactPhotosShowViewHelper::data($photo, $contact),
        ]);
    }
}
