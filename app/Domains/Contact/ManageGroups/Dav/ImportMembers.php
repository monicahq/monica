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
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        $group = $this->getExistingGroup($vcard);

        if ($group !== null) {
            $data = $this->importMembers($vcard);

            $this->updateGroupMembers($group, collect($data['members']));
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
            throw new ModelNotFoundException();
        }

        return $group;
    }

    /**
     * Import members of the group.
     */
    public function importMembers(VCard $entry): array
    {
        $members = $entry->MEMBER;

        $data = [];

        if ($members === null) {
            return $data;
        }

        $data['members'] = collect($members)
            ->map(fn ($member) => $this->formatValue((string) $member))
            ->toArray();

        return $data;
    }

    private function updateGroupMembers(Group $group, Collection $members): void
    {
        // Contacts to remove
        $contacts = $group->contacts
            ->groupBy(fn (Contact $contact): string => $members->contains($contact->distant_uuid) || $members->contains($contact->id)
                    ? 'keep'
                    : 'remove'
            );

        $contacts->get('remove', collect())
            ->each(fn ($contact) => RemoveContactFromGroup::dispatch([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'group_id' => $group->id,
                'contact_id' => $contact->id,
            ])->onQueue('high'));

        // Contacts to add
        $members->filter(fn ($member) => ! $contacts->get('keep', collect())->contains('distant_uuid', $member)
            || ! $contacts->get('keep', collect())->contains('id', $member))
            ->each(function ($member) use ($group) {
                $groupData = [
                    'account_id' => $this->account()->id,
                    'vault_id' => $this->vault()->id,
                    'author_id' => $this->author()->id,
                    'group_id' => $group->id,
                ];

                $contact = Contact::firstWhere('distant_uuid', $member);

                if ($contact === null) {
                    $contact = Contact::find($member);
                }
                if ($contact === null) {
                    // Contact not found !
                    return;
                }

                $groupData['contact_id'] = $contact->id;

                AddContactToGroup::dispatch($groupData)->onQueue('default');
            });
    }
}
