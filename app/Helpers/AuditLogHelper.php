<?php

namespace App\Helpers;

use App\Models\Instance\AuditLog;

class AuditLogHelper
{
    /**
     * Return an sentence explaining what the log contains.
     * A log is stored in a json file and needs some kind of processing to make
     * it understandable by a human.
     *
     * @param AuditLog $log
     * @return string
     */
    public static function processAuditLog(AuditLog $log): string
    {
        $sentence = '';

        if ($log->action == 'contact_created') {
            $sentence = trans('app.log_contact_created');
        }

        return $sentence;
    }
}
