<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\AuditLogHelper;
use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    /**
     * Display the page listing all the audit logs.
     */
    public function index()
    {
        $logs = auth()->user()->account->auditLogs()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('settings.auditlog.index')
            ->withLogsCollection(AuditLogHelper::getCollectionOfAuditForSettings($logs))
            ->withLogsPagination($logs);
    }
}
