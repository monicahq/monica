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
class ExportKind extends Exporter implements ExportVCardResource
{
    /**
     * @param  Group  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $kind = (string) collect($vcard->select('X-ADDRESSBOOKSERVER-KIND'))->first();

        if (! empty($kind)) {
            $vcard->remove('X-ADDRESSBOOKSERVER-KIND');
            $vcard->add('X-ADDRESSBOOKSERVER-KIND', 'group');

            return;
        }

        $vcard->remove('KIND');
        $vcard->add('KIND', 'group');
    }
}
