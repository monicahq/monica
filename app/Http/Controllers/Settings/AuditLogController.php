<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditLogController extends Controller
{
    /**
     * Display the page listing all the audit logs.
     */
    public function index()
    {
        $logs = auth()->user()->account->logs()
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $logsCollection = collect();

        foreach ($logs as $log) {
            // the log is about a contact
            if (isset($log->object->{'contact_id'})) {
                try {
                    $contact = Contact::findOrFail($log->object->{'contact_id'});
                    $description = trans('app.log_'.$log->action.'_with_name_with_link', [
                        'link' => '/people/'.$contact->hashId(),
                        'name' => $contact->name, ],
                    );
                } catch (ModelNotFoundException $e) {
                    $description = trans('app.log_'.$log->action.'_with_name', ['name' => $log->object->{'contact_name'}]);
                }

                $logsCollection->push([
                    'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                    'description' => $description,
                    'audited_at' => DateHelper::getShortDateWithTime($log->audited_at),
                ]);
            }
        }

        return view('settings.auditlog.index')
            ->withLogs($logsCollection);
    }
}
