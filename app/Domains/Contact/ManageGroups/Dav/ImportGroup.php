<?php

namespace App\Domains\Contact\Managegroups\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use Sabre\VObject\Component\VCard;

#[Order(2)]
class ImportGroup extends Importer implements ImportVCardResource
{
    /**
     * Can import Group.
     */
    public function can(VCard $vcard): bool
    {
        $kind = (string) ($vcard->KIND ?? $vcard->select('X-ADDRESSBOOKSERVER-KIND'));

        return $kind === 'group';
    }

    /**
     * Import group.
     */
    public function import(?Contact $contact, VCard $vcard): ?Contact
    {
        return null;
    }
}
