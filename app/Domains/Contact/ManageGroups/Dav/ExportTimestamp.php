<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Group>
 */
#[Order(1000)]
#[VCardType(Group::class)]
class ExportTimestamp implements ExportVCardResource
{
    /**
     * @param  Group  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $resource->updated_at->format('Ymd\\THis\\Z');
    }
}
