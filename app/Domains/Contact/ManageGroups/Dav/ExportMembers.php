<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(2)]
#[VCardType(Group::class)]
class ExportMembers extends Exporter implements ExportVCardResource
{
    public function export(VCardResource $card, VCard $vcard): void
    {
        $vcard->remove('MEMBER');

        /** @var Group */
        $group = $card;
        $group->contacts()->each(fn (Contact $contact) => $vcard->add('MEMBER', $contact->distant_uuid ?? $contact->id));
    }
}
