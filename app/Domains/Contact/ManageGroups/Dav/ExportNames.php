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
#[Order(10)]
class ExportNames extends Exporter implements ExportVCardResource
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
        $vcard->remove('FN');
        $vcard->remove('N');

        $vcard->add('FN', $this->escape($resource->name));

        $vcard->add('N', [
            $this->escape($resource->name),
        ]);
    }
}
