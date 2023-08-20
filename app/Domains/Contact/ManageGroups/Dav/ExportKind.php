<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Group>
 */
#[Order(1)]
class ExportKind extends Exporter implements ExportVCardResource
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
        $kind = collect($vcard->select('X-ADDRESSBOOKSERVER-KIND'))->first();

        if ($kind) {
            $vcard->remove($kind);
            $vcard->add('X-ADDRESSBOOKSERVER-KIND', 'group');

            return;
        }

        $vcard->remove('KIND');
        $vcard->add('KIND', 'group');
    }
}
