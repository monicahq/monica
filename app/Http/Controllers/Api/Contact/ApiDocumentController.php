<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Document\Document as DocumentResource;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Services\Contact\Document\DestroyDocument;
use App\Services\Contact\Document\UploadDocument;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiDocumentController extends ApiController
{
    /**
     * Get the list of documents.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @param int $contactId
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function contact(Request $request, $contactId)
    {
        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
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
     * @param Request $request
     * @param int $documentId
     *
     * @return DocumentResource|\Illuminate\Http\JsonResponse
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

    /**
     * Store a document.
     *
     * @param Request $request
     *
     * @return DocumentResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $document = app(UploadDocument::class)->execute(
                $request->except(['account_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new DocumentResource($document);
    }

    /**
     * Destroy a document.
     *
     * @param Request $request
     * @param int $documentId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $documentId)
    {
        try {
            app(DestroyDocument::class)->execute([
                'account_id' => auth()->user()->account_id,
                'document_id' => $documentId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $documentId);
    }
}
