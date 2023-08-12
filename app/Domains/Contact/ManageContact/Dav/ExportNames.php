<?php

namespace App\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Contact>
 */
#[Order(1)]
#[VCardType(Contact::class)]
class ExportNames extends Exporter implements ExportVCardResource
{
    /**
     * @param  Contact  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('FN');
        $vcard->remove('N');
        $vcard->remove('NICKNAME');

        $vcard->add('FN', $this->escape($resource->name));

        $vcard->add('N', [
            $this->escape($resource->last_name),
            $this->escape($resource->first_name),
            $this->escape($resource->middle_name),
        ]);

        if (! empty($resource->nickname)) {
            $vcard->add('NICKNAME', $this->escape($resource->nickname));
        }
    }
}
