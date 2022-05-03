<?php

namespace App\Helpers;

use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditLogHelper
{
    /**
     * Prepare a collection of audit logs that is displayed on the Settings page.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection<array-key, \App\Models\Instance\AuditLog>  $logs
     * @return Collection
     */
    public static function getCollectionOfAudits($logs): Collection
    {
        $logsCollection = collect();

        foreach ($logs as $log) {
            $object = null;
            $link = null;

            // the log is about a contact
            if (isset($log->object->{'contact_id'})) {
                try {
                    // check if the contact that the log is about still exists
                    // in that case, we will display a link to point to this contact
                    $contact = Contact::findOrFail($log->object->{'contact_id'});
                    $object = $contact->name;
                    $link = route('people.show', ['contact' => $contact]);
                } catch (ModelNotFoundException $e) {
                    // the contact doesn't exist anymore, we don't need a link, we'll only display a name
                    $object = $log->object->{'contact_name'};
                }
                $description = trans('logs.settings_log_'.$log->action.'_with_name', ['name' => $object]);
            } else {
                $description = trans('logs.settings_log_'.$log->action, ['name' => $log->object->{'name'}]);
            }

            $logsCollection->push([
                'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                'description' => $description,
                'link' => $link,
                'object' => $object,
                'audited_at' => $log->audited_at,
            ]);
        }

        return $logsCollection;
    }
}
