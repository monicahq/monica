<?php

namespace App\ViewHelpers\Contact;

use App\Models\Contact\Contact;
use Illuminate\Support\Collection;

class ContactIndexViewHelper
{
    /**
     * Prepare a collection of audit logs.
     *
     * @param mixed $logs
     * @return array
     */
    public static function information(Contact $contact): array
    {
        return [
            'work' => [
                'job' => $contact->job,
                'company' => $contact->company->name,
            ],
        ];
    }
}
