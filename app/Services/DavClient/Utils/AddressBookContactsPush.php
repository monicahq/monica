<?php

namespace App\Services\DavClient\Utils;

use App\Jobs\Dav\PushVCard;
use Illuminate\Support\Arr;
use App\Jobs\Dav\DeleteVCard;
use Illuminate\Support\Collection;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Traits\WithSyncDto;
use App\Services\DavClient\Utils\Model\ContactPushDto;

class AddressBookContactsPush
{
    use WithSyncDto;

    /**
     * Push contacts to the distant server.
     *
     * @param  SyncDto  $sync
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array<array-key, string>|null  $localChanges
     * @return Collection
     */
    public function execute(SyncDto $sync, Collection $changes, ?array $localChanges): Collection
    {
        $this->sync = $sync;

        $changes = $this->preparePushChangedContacts($changes, Arr::get($localChanges, 'modified', []));
        $added = $this->preparePushAddedContacts(Arr::get($localChanges, 'added', []));
        $deleted = $this->prepareDeletedContacts(Arr::get($localChanges, 'deleted', []));

        return $changes
            ->union($added)
            ->union($deleted)
            ->filter(function ($c) {
                return $c !== null;
            });
    }

    /**
     * Get list of requests to push new contacts.
     *
     * @param  array  $contacts
     * @return Collection
     */
    private function preparePushAddedContacts(array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
            ->map(function (string $uri): ?PushVCard {
                $card = $this->backend()->getCard($this->sync->addressBookName(), $uri);

                return $card === false ? null
                    : new PushVCard($this->sync->subscription,
                        new ContactPushDto(
                            $uri,
                            $card['distant_etag'],
                            $card['carddata'],
                            $card['contact_id']
                        )
                    );
            });
    }

    /**
     * Get list of requests to delete contacts.
     *
     * @param  array  $contacts
     * @return Collection
     */
    private function prepareDeletedContacts(array $contacts): Collection
    {
        // All removed contact must be deleted
        return collect($contacts)
            ->map(function (string $uri): DeleteVCard {
                return new DeleteVCard($this->sync->subscription, $uri);
            });
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array  $contacts
     * @return Collection
     */
    private function preparePushChangedContacts(Collection $changes, array $contacts): Collection
    {
        $backend = $this->backend();

        $refreshIds = $changes->map(function (ContactDto $contact) use ($backend) {
            return $backend->getUuid($contact->uri);
        });

        // We don't push contact that have just been pulled
        return collect($contacts)
            ->reject(function (string $uri) use ($refreshIds, $backend): bool {
                $uuid = $backend->getUuid($uri);

                return $refreshIds->contains($uuid);
            })->map(function (string $uri) use ($backend): ?PushVCard {
                $card = $backend->getCard($this->sync->addressBookName(), $uri);

                return $card === false ? null
                    : new PushVCard($this->sync->subscription,
                        new ContactPushDto(
                            $uri,
                            $card['distant_etag'],
                            $card['carddata'],
                            $card['contact_id'],
                            $card['distant_etag'] !== null ? ContactPushDto::MODE_MATCH_ETAG : ContactPushDto::MODE_MATCH_ANY
                        )
                    );
            });
    }
}
