<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(1)]
#[VCardType(Group::class)]
class ExportNames extends Exporter implements ExportVCardResource
{
    public function export(VCardResource $group, VCard $vcard): void
    {
        $vcard->remove('FN');
        $vcard->remove('N');
        $vcard->remove('KIND');

        $vcard->add('FN', $this->escape($group->name));

        $vcard->add('N', [
            $this->escape($group->name),
        ]);

        $vcard->add('KIND', 'group');
    }
}
