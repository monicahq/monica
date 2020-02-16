<?php

namespace App\Http\Controllers\Contacts;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\GenderHelper;
use App\Models\Contact\Contact;
use App\Helpers\PaginatorHelper;
use Illuminate\Http\JsonResponse;
use App\ViewHelpers\ContactHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UpdateLastConsultedDate;
use App\ViewHelpers\ContactListHelper;
use App\Services\Contact\Contact\CreateContact;

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
            'content' => ContactHelper::getListOfAuditLogs($logs),
            'paginator' => PaginatorHelper::getData($logs),
        ];

        return response()->json([
            'data' => $logs,
        ], 200);
    }
}
