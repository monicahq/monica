<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageGroups\Services\AddContactToGroup;
use App\Domains\Contact\ManageGroups\Services\RemoveContactFromGroup;
use App\Models\Contact;
use App\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;

#[Order(11)]
class ImportMembers extends Importer implements ImportVCardResource
{
    /**
     * Test if the Card is handled by this importer.
     */
    public function handle(VCard $vcard): bool
    {
        return $this->kind($vcard) === 'group';
    }

    /**
     * Import group.
     */
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        $group = $this->getExistingGroup($vcard);

        if ($group !== null) {
            $members = $this->importMembers($vcard);

            $this->updateGroupMembers($group, $members);
        }

        return $group;
    }

    /**
     * Get existing group.
     */
    protected function getExistingGroup(VCard $vcard): ?Group
    {
        $group = null;

        if (($uri = Arr::get($this->context->data, 'uri')) !== null) {
            $group = Group::firstWhere([
                'vault_id' => $this->vault()->id,
                'distant_uri' => $uri,
            ]);
        }

        if ($group === null && ($groupId = $this->getUid($vcard)) !== null) {
            $group = Group::firstWhere([
                'vault_id' => $this->vault()->id,
                'distant_uuid' => $groupId,
            ]);
        }

        if ($group !== null && $group->vault_id !== $this->vault()->id) {
            throw new ModelNotFoundException;
        }

        return $group;
    }

    /**
     * Import members of the group.
     *
     * @return Collection<array-key,string>
     */
    public function importMembers(VCard $entry): Collection
    {
        $members = $entry->MEMBER;

        if ($members === null) {
            $members = $entry->select('X-ADDRESSBOOKSERVER-MEMBER');
        }

        if ($members === null) {
            return collect();
        }

        return collect($members)
            ->map(fn ($member): string => $this->formatValue((string) $member));
    }

    /**
     * Update group members.
     *
     * @param  Collection<array-key,string>  $members
     */
    private function updateGroupMembers(Group $group, Collection $members): void
    {
        // Contacts to remove
        $contacts = $group->contacts
            ->groupBy(fn (Contact $contact): string => $members->contains($contact->distant_uuid) || $members->contains($contact->id)
                    ? 'keep'
                    : 'remove'
            );

        $contacts->get('remove', collect())
            ->each(fn (Contact $contact) => RemoveContactFromGroup::dispatch([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'group_id' => $group->id,
                'contact_id' => $contact->id,
            ])->onQueue('high'));

        // Contacts to add
        $members->filter(fn (string $member): bool => ! $contacts->get('keep', collect())->contains('distant_uuid', $member)
            || ! $contacts->get('keep', collect())->contains('id', $member))
            ->map(function (string $member): ?string {
                $contact = Contact::firstWhere('distant_uuid', $member);

                if ($contact === null) {
                    $contact = Contact::find($member);
                }
                if ($contact === null) {
                    // Contact not found !
                    return null;
                }

                return $contact->id;
            })
            ->filter()
            ->each(fn (string $contactId) => AddContactToGroup::dispatch([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'group_id' => $group->id,
                'contact_id' => $contactId,
            ])->onQueue('default')
            );
    }
}
