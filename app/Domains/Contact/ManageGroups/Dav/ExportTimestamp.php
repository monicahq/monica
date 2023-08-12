<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Group>
 */
#[Order(1000)]
class ExportTimestamp implements ExportVCardResource
{
    public function getType(): string
    {
        return Group::class;
    }

    /**
     * @param  Group  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $resource->updated_at->format('Ymd\\THis\\Z');
    }
}
