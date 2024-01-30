<?php

namespace App\Domains\Contact\ManageGifts\Web\Controllers;

use App\Domains\Contact\ManageGifts\Web\ViewHelpers\GiftsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactGiftsController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        $gifts = $contact->gifts()->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Vault/Contact/Gifts/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GiftsIndexViewHelper::data($contact, $gifts, Auth::user()),
            'paginator' => PaginatorHelper::getData($gifts),
        ]);
    }
}
