<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Http\Controllers\Controller;
use App\Http\Resources\Document\Document as DocumentResource;
use App\Services\Contact\Document\UploadDocument;

class DocumentsController extends Controller
{
    /**
     * Display the list of documents.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Contact $contact)
    {
        $documents = $contact->documents()->get();
        return DocumentResource::collection($documents);
    }

    /**
     * Store the document.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        return (new UploadDocument)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'document' => $request->document,
        ]);
    }

    /**
     * Delete the conversation.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Conversation $conversation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Contact $contact, Conversation $conversation)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'conversation_id' => $conversation->id,
        ];

        try {
            (new DestroyConversation)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.conversation_delete_success'));
    }
}
