<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Models\Contact;
use App\Models\Group;
use Sabre\VObject\Component\VCard;

/**
 * @implements ExportVCardResource<Group>
 */
#[Order(20)]
class ExportMembers extends Exporter implements ExportVCardResource
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

        // https://datatracker.ietf.org/doc/html/rfc6350#section-6.6.5
        $this->exportType($resource, $vcard, $kind ? 'X-ADDRESSBOOKSERVER-MEMBER' : 'MEMBER');
    }

    private function exportType($resource, VCard $vcard, string $type): void
    {
        $contacts = $resource->contacts
            ->map(fn (Contact $contact): string => $contact->distant_uuid ?? $contact->id)
            ->sort();

        $current = collect($vcard->select($type));
        $members = $current
            ->map(fn ($member): string => $this->formatValue((string) $member));

        // Add new members
        foreach ($contacts as $contact) {
            if (! $members->contains($contact)) {
                $vcard->add($type, $contact);
            }
        }

        // Remove old members
        foreach ($current as $member) {
            if (! $contacts->contains($this->formatValue((string) $member))) {
                $vcard->remove($member);
            }
        }
    }
}
