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
#[Order(1)]
class ExportNames extends Exporter implements ExportVCardResource
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
        $vcard->remove('FN');
        $vcard->remove('N');
        $vcard->remove('NICKNAME');

        // https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.1
        $vcard->add('FN', $this->escape($resource->name));

        // https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.2
        $vcard->add('N', [
            $this->escape($resource->last_name),
            $this->escape($resource->first_name),
            $this->escape($resource->middle_name),
        ]);

        if (! empty($resource->nickname)) {
            // https://datatracker.ietf.org/doc/html/rfc6350#section-6.2.3
            $vcard->add('NICKNAME', $this->escape($resource->nickname));
        }
    }
}
