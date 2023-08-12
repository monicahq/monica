<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardType;
use App\Models\Contact;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

#[Order(20)]
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
        $contacts = $resource->contacts
            ->map(fn (Contact $contact) => $contact->distant_uuid ?? $contact->id)
            ->sort();

        $current = collect($vcard->select('MEMBER'));
        $members = $current
            ->map(fn ($member) => (string) $member);

        foreach ($contacts as $contact) {
            if (! $members->contains($contact)) {
                $vcard->add('MEMBER', $contact);
            }
        }

        foreach ($current as $member) {
            if (! $contacts->contains((string) $member)) {
                $vcard->remove($member);
            }
        }
    }
}
