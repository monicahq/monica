<?php

namespace App\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Importer;
use App\Domains\Contact\Dav\ImportVCardResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\ManageGroups\Services\AddContactToGroup;
use App\Domains\Contact\ManageGroups\Services\CreateGroup;
use App\Domains\Contact\ManageGroups\Services\RemoveContactFromGroup;
use App\Domains\Contact\ManageGroups\Services\UpdateGroup;
use App\Models\Contact;
use App\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
    public function import(VCard $vcard, ?VCardResource $result): ?VCardResource
    {
        $group = $this->getExistingGroup($vcard);

        $data = $this->getGroupData($group);
        $original = $data;

        $data = $this->importUid($data, $vcard);
        $data = $this->importNames($data, $vcard);
        $data = $this->importMembers($data, $vcard);

        if ($group !== null) {
            $group = app(CreateGroup::class)->execute($data);
        } elseif ($data !== $original) {
            $group = app(UpdateGroup::class)->execute($data);
        }

        $this->updateGroupMembers($group, collect($data['members']));

        if ($this->context->external && $group->distant_uuid === null) {
            $group->distant_uuid = $this->getUid($vcard);
            $group->save();
        }

        return Group::withoutTimestamps(function () use ($group): Group {
            $uri = Arr::get($this->context->data, 'uri');
            if ($this->context->external) {
                $group->distant_etag = Arr::get($this->context->data, 'etag');
                $group->distant_uri = $uri;
            }

            $group->save();

            return $group;
        });
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

        if ($group === null) {
            $groupId = $this->getUid($vcard);
            if ($groupId !== null) {
                $group = Group::firstWhere([
                    'vault_id' => $this->vault()->id,
                    'id' => $groupId,
                ]);
            }
        }

        if ($group !== null && $group->vault_id !== $this->vault()->id) {
            throw new ModelNotFoundException();
        }

        return $group;
    }

    /**
     * Get group data.
     */
    private function getGroupData(?Group $group): array
    {
        $result = [
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'name' => $group ? $group->name : null,
        ];

        if ($group) {
            $result['members'] = [];
            $group->contacts->each(function (Contact $contact) use (&$result) {
                $c = ['id' => $contact->id];
                if ($contact->distant_uuid !== null) {
                    $c['distant_uuid'] = $contact->distant_uuid;
                }
                $result['members'][] = $c;
            });
        }

        return $result;
    }

    /**
     * Import names of the group.
     */
    public function importNames(array $contactData, VCard $entry): array
    {
        if ($this->hasNameInN($entry)) {
            $contactData = $this->importNameFromN($contactData, $entry);
        } elseif ($this->hasFN($entry)) {
            $contactData = $this->importNameFromFN($contactData, $entry);
        } else {
            throw new \LogicException('Check if you can import entry!');
        }

        return $contactData;
    }

    private function hasNameInN(VCard $entry): bool
    {
        return $entry->N !== null && ! empty(Arr::get($entry->N->getParts(), '1'));
    }

    private function hasFN(VCard $entry): bool
    {
        return ! empty((string) $entry->FN);
    }

    private function importNameFromN(array $contactData, VCard $entry): array
    {
        $parts = $entry->N->getParts();

        $contactData['name'] = $this->formatValue(Arr::get($parts, '0'));

        return $contactData;
    }

    private function importNameFromFN(array $contactData, VCard $entry): array
    {
        $contactData['name'] = (string) $entry->FN;

        return $contactData;
    }

    /**
     * Import members of the group.
     */
    public function importMembers(array $data, VCard $entry): array
    {
        $members = $entry->MEMBER;

        $data['members'] = [];

        if ($members === null) {
            return $data;
        }

        $data['members'] = collect($members)
            ->map(fn ($member) => ['distant_uuid' => $this->formatValue((string) $member)])
            ->toArray();

        return $data;
    }

    private function updateGroupMembers(Group $group, Collection $members): void
    {
        // Contacts to remove
        $contacts = $group->contacts
            ->groupBy(fn (Contact $contact): string => $members->contains('distant_uuid', $contact->distant_uuid) || $members->contains('id', $contact->id)
                    ? 'keep'
                    : 'remove'
            );

        $contacts->get('remove')
            ->each(fn ($contact) => RemoveContactFromGroup::dispatch([
                'account_id' => $this->account()->id,
                'vault_id' => $this->vault()->id,
                'author_id' => $this->author()->id,
                'group_id' => $group->id,
                'contact_id' => $contact->id,
            ])->onQueue('high'));

        // Contacts to add
        $members->filter(fn ($member) => ! $contacts->get('keep')->contains('distant_uuid', $member['distant_uuid'])
            || ! $contacts->get('keep')->contains('id', $member['id']))
            ->each(function ($member) use ($group) {
                $groupData = [
                    'account_id' => $this->account()->id,
                    'vault_id' => $this->vault()->id,
                    'author_id' => $this->author()->id,
                    'group_id' => $group->id,
                ];

                if (isset($member['distant_uuid'])) {
                    $contact = Contact::where('distant_uuid', $member['distant_uuid'])->firstOrFail();
                    $groupData['contact_id'] = $contact->id;
                } elseif (isset($member['id'])) {
                    $groupData['contact_id'] = $member['id'];
                } else {
                    throw new \LogicException('Check if you can import entry!');
                }

                AddContactToGroup::dispatch($groupData)->onQueue('default');
            });
    }
}
