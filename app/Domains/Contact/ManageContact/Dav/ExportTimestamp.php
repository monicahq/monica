<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

#[Order(1000)]
#[VCardType(Contact::class)]
/**
 * @implements ExportVCardResource<Contact>
 *
 * @template-implements ExportVCardResource<Contact>
 */
class ExportTimestamp implements ExportVCardResource
{
    /**
     * @param  Contact  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $resource->updated_at->format('Ymd\\THis\\Z');
    }
}
