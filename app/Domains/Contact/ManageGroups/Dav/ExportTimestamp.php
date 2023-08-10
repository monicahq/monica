<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(1000)]
#[VCardType(Group::class)]
class ExportTimestamp implements ExportVCardResource
{
    public function export(VCardResource $group, VCard $vcard): void
    {
        $vcard->remove('REV');

        $vcard->REV = $group->updated_at->format('Ymd\\THis\\Z');
    }
}
