<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AuditLog\AuditLog as AuditLogResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiAuditLogController extends ApiController
{
    /**
     * Get the list of the audit logs.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
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
