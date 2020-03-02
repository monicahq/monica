<?php

namespace App\Http\Controllers\Contacts;

use Inertia\Response;
use App\Models\Contact\Contact;
use App\Helpers\PaginatorHelper;
use Illuminate\Http\JsonResponse;
use App\ViewHelpers\ContactViewHelper;
use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    /**
     * Show the audit logs.
     *
     * @param Contact $contact
     * @return JsonResponse
     */
    public function index(Contact $contact): JsonResponse
    {
        // audit logs
        $logs = $contact->logs()->latest()->paginate(10);

        $logs = [
            'content' => ContactViewHelper::getListOfAuditLogs($logs),
            'paginator' => PaginatorHelper::getData($logs),
        ];

        return response()->json([
            'data' => $logs,
        ], 200);
    }
}
