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
class ExportLabels extends Exporter implements ExportVCardResource
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
        // https://datatracker.ietf.org/doc/html/rfc6350#section-6.7.1
        $vcard->remove('CATEGORIES');

        if ($resource->labels->count() > 0) {
            $vcard->add('CATEGORIES', $resource->labels->pluck('name')->toArray());
        }
    }
}
