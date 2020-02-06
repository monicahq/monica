<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\AuditLog\AuditLog as AuditLogResource;

class ApiAuditLogController extends ApiController
{
    /**
     * Get the list of the audit logs for the given contact.
     *
     * @param Request $request
     * @param $contactId
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request, $contactId)
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
