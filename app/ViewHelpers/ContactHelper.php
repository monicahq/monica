<?php

namespace App\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;

/**
 * These are methods used on the contact page.
 *
 * We use raw sql queries for performance reasons. If we use Eloquent,
 * this will drastically affect performances as each model will be
 * hydrated and memory allocated. As this function is used on the list of
 * contacts, we need it to be really performant.
 */
class ContactHelper
{
    /**
     * Prepare a collection of audit logs.
     *
     * @param mixed $logs
     * @return Collection
     */
    public static function getListOfAuditLogs($logs): Collection
    {
        $logsCollection = collect();

        foreach ($logs as $log) {
            $description = trans('app.contact_log_'.$log->action);

            $logsCollection->push([
                'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                'description' => $description,
                'audited_at' => DateHelper::getShortDateWithTime($log->audited_at),
            ]);
        }

        return $logsCollection;
    }
}
