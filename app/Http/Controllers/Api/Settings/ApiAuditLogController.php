<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\AuditLog\AuditLog as AuditLogResource;

class ApiAuditLogController extends ApiController
{
    /**
     * Get the list of the audit logs.
     *
     * @param Request $request
     * @return JsonResource|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $logs = auth()->user()->account->logs()
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return AuditLogResource::collection($logs);
    }
}
