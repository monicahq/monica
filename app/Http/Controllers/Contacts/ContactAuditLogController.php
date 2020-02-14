<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
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

        $logsCollection = collect();

        foreach ($logs as $log) {
            $description = trans('app.contact_log_'.$log->action);

            $logsCollection->push([
                'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                'description' => $description,
                'audited_at' => DateHelper::getShortDateWithTime($log->audited_at),
            ]);
        }

        return view('people.auditlogs.index')
            ->withContact($contact)
            ->withLogs($logsCollection)
            ->withLogsPagination($logs);
    }
}
