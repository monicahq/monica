<?php

namespace App\Services\DavClient\Utils;

use App\Jobs\Dav\PushVCard;
use Illuminate\Support\Collection;
use IlluminateAgnostic\Collection\Support\Arr;
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

        return $changes->union($added)
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

                return $card !== false
                    ? new PushVCard($this->sync->subscription, new ContactPushDto($uri, $card['etag'], $card['carddata']))
                    : null;
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

                return $card !== false
                    ? new PushVCard($this->sync->subscription, new ContactPushDto($uri, $card['etag'], $card['carddata'], ContactPushDto::MODE_MATCH_ETAG))
                    : null;
            });
    }
}
