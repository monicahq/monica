<?php

namespace App\ViewHelpers\Contact;

use App\Models\Contact\Contact;
use Illuminate\Support\Collection;

class ContactIndexViewHelper
{
    /**
     * Get information about the contact.
     *
     * @param Contact $contact
     * @return array
     */
    public static function information(Contact $contact): array
    {
        $company = $contact->company;

        return [
            'work' => [
                'job' => $contact->job,
                'company' => $company ? $contact->company->name : null,
            ],
        ];
    }
}
