<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\AccountHelper;
use App\Helpers\AuditLogHelper;
use App\Models\Contact\Contact;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    /**
     * Show the audit logs.
     *
     * @param Contact $contact
     * @return JsonResponse
     */
    public function index()
    {
        $logs = auth()->user()->account->auditLogs()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('settings.auditlog.index')
            ->withLogsCollection(AuditLogHelper::getCollectionOfAuditForSettings($logs))
            ->withAccountHasLimitations($accountHasLimitations)
            ->withLogsPagination($logs);
    }
}
