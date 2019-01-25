<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Document\Document as DocumentResource;

class ApiDocumentController extends ApiController
{
    /**
     * Get the list of documents.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $documents = auth()->user()->account->documents()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return DocumentResource::collection($documents);
    }

    /**
     * Get the list of documents for a specific contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function documents(Request $request, $contactId)
    {
        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $documents = auth()->user()->account->documents()
                ->where('contact_id', $contactId)
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return DocumentResource::collection($documents);
    }

    /**
     * Get the detail of a given document.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $documentId)
    {
        try {
            $document = Document::where('account_id', auth()->user()->account_id)
                ->findOrFail($documentId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new DocumentResource($document);
    }
}
