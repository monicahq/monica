<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Http\Controllers\Controller;
use App\Services\Contact\Document\UploadDocument;
use App\Services\Contact\Document\DestroyDocument;
use App\Http\Resources\Document\Document as DocumentResource;

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
     * Delete the document.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Document $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Contact $contact, Document $document)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'document_id' => $document->id,
        ];

        try {
            (new DestroyDocument)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
    }
}
