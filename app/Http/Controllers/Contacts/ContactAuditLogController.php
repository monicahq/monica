<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\AuditLogHelper;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;

class ContactAuditLogController extends Controller
{
    /**
     * Display the page listing all the audit logs.
     */
    public function index(Contact $contact)
    {
        $logs = $contact->logs()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('people.auditlogs.index')
            ->withContact($contact)
            ->withLogsCollection(AuditLogHelper::getCollectionOfAudits($logs))
            ->withLogsPagination($logs);
    }
}
