<?php

namespace App\Contact\ManageNotes\Web\Controllers;

use Inertia\Inertia;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Contact\ManageNotes\Web\ViewHelpers\NotesIndexViewHelper;

class ContactNotesController extends Controller
{
    public function index(Request $request, int $vaultId, int $contactId)
    {
        $vault = Vault::findOrFail($vaultId);
        $contact = Contact::findOrFail($contactId);

        $notes = $contact->notes()->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Vault/Contact/Notes/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => NotesIndexViewHelper::data($contact, $notes, Auth::user()),
            'paginator' => PaginatorHelper::getData($notes),
        ]);
    }
}
