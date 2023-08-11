<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(1)]
#[VCardType(Group::class)]
/**
 * @implements ExportVCardResource<Group>
 *
 * @template-implements ExportVCardResource<Group>
 */
class ExportNames extends Exporter implements ExportVCardResource
{
    /**
     * @param  Group  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('FN');
        $vcard->remove('N');
        $vcard->remove('KIND');

        $vcard->add('FN', $this->escape($resource->name));

        $vcard->add('N', [
            $this->escape($resource->name),
        ]);

        $vcard->add('KIND', 'group');
    }
}
