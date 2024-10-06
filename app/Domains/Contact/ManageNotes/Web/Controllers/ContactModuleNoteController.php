<?php

namespace App\Domains\Contact\ManageNotes\Web\Controllers;

use App\Domains\Contact\ManageNotes\Services\CreateNote;
use App\Domains\Contact\ManageNotes\Services\DestroyNote;
use App\Domains\Contact\ManageNotes\Services\UpdateNote;
use App\Domains\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleNoteController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'emotion_id' => $request->input('emotion'),
        ];

        $note = (new CreateNote)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleNotesViewHelper::dto($contact, $note, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $noteId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'note_id' => $noteId,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'emotion_id' => $request->input('emotion'),
        ];

        $note = (new UpdateNote)->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleNotesViewHelper::dto($contact, $note, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $noteId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'note_id' => $noteId,
        ];

        (new DestroyNote)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
