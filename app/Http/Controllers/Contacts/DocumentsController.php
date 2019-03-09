<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Document\UploadDocument;
use App\Services\Contact\Document\DestroyDocument;
use App\Http\Resources\Document\Document as DocumentResource;

class DocumentsController extends Controller
{
    use JsonRespondController;

    /**
     * Display the list of documents.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
     *
     * @return Document
     */
    public function store(Request $request, Contact $contact): Document
    {
        return app(UploadDocument::class)->execute([
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
     *
     * @return null|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Document $document)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'document_id' => $document->id,
        ];

        try {
            app(DestroyDocument::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
    }
}
