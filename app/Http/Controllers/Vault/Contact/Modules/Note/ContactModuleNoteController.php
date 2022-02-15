<?php

namespace App\Http\Controllers\Vault\Contact\Modules\Note;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Contact\ManageNote\CreateNote;
use App\Services\Contact\ManageNote\UpdateNote;
use App\Services\Contact\ManageNote\DestroyNote;
use App\Http\Controllers\Vault\Contact\Modules\Note\ViewHelpers\ModuleNotesViewHelper;

class ContactModuleNoteController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
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

    public function update(Request $request, int $vaultId, int $contactId, int $noteId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
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

    public function destroy(Request $request, int $vaultId, int $contactId, int $noteId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
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
