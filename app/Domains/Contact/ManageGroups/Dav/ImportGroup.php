<?php

namespace App\Domains\Contact\Managegroups\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use Sabre\VObject\Component\VCard;

#[Order(2)]
class ImportGroup extends Importer implements ImportVCardResource
{
    /**
     * Can import Group.
     */
    public function can(VCard $vcard): bool
    {
        $kind = (string) $vcard->KIND;
        if ($kind == null) {
            $kind = (string) collect($vcard->select('X-ADDRESSBOOKSERVER-KIND'))->first();
        }

        return $kind === 'group';
    }

    /**
     * Import group.
     */
    public function import(VCard $vcard, mixed $entry): mixed
    {
        return null;
    }
}
