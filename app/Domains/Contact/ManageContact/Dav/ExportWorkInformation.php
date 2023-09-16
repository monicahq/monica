<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(40)]
class ExportWorkInformation extends Exporter implements ExportVCardResource
{
    public function getType(): string
    {
        return Contact::class;
    }

    /**
     * @param  Contact  $resource
     */
    public function export(mixed $resource, VCard $vcard): void
    {
        $vcard->remove('ORG');
        $vcard->remove('TITLE');

        if (($company = $resource->company) !== null) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.6.4
            $vcard->add('ORG', $this->escape($company->name));
        }

        if (! empty($resource->job_position)) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.6.1
            $vcard->add('TITLE', $this->escape($resource->job_position));
        }
    }
}
