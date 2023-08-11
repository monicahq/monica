<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(2)]
#[VCardType(Group::class)]
/**
 * @implements ExportVCardResource<Group>
 *
 * @template-implements ExportVCardResource<Group>
 */
class ExportMembers extends Exporter implements ExportVCardResource
{
    /**
     * @param  Group  $resource
     */
    public function export($resource, VCard $vcard): void
    {
        $vcard->remove('MEMBER');

        $resource->contacts()
            ->each(fn (Contact $contact) => $vcard->add('MEMBER', $contact->distant_uuid ?? $contact->id));
    }
}
