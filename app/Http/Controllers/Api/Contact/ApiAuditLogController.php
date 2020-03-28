<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AuditLog\AuditLog as AuditLogResource;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiAuditLogController extends ApiController
{
    /**
     * Get the list of the audit logs for the given contact.
     *
     * @param Request $request
     * @param int $contactId
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request, int $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $logs = $contact->logs()
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return AuditLogResource::collection($logs);
    }
}
