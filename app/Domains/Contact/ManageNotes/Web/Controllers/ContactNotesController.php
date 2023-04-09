<?php

namespace App\Domains\Contact\ManageNotes\Web\Controllers;

use App\Domains\Contact\ManageNotes\Web\ViewHelpers\NotesIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactNotesController extends Controller
{
    public function index(Request $request, string $vaultId, string $contactId)
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
