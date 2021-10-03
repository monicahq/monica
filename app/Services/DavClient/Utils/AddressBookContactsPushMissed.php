<?php

namespace App\Services\DavClient\Utils;

use App\Jobs\Dav\PushVCard;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use IlluminateAgnostic\Collection\Support\Arr;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Model\ContactPushDto;

class AddressBookContactsPushMissed
{
    /**
     * @var SyncDto
     */
    private $sync;

    /**
     * Push contacts to the distant server.
     *
     * @param  SyncDto  $sync
     * @param  array<array-key, string>|null  $localChanges
     * @param  Collection<array-key, ContactDto>  $distContacts
     * @param  Collection<array-key, Contact>  $localContacts
     * @return Collection
     */
    public function execute(SyncDto $sync, ?array $localChanges, Collection $distContacts, Collection $localContacts): Collection
    {
        $this->sync = $sync;

        $missings = $this->preparePushMissedContacts(Arr::get($localChanges, 'added', []), $distContacts, $localContacts);

        return app(AddressBookContactsPush::class)
            ->execute($sync, collect(), $localChanges)
            ->union($missings);
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param  array<array-key, string>  $added
     * @param  Collection<array-key, ContactDto>  $distContacts
     * @param  Collection<array-key, Contact>  $localContacts
     * @return Collection
     */
    private function preparePushMissedContacts(array $added, Collection $distContacts, Collection $localContacts): Collection
    {
        /** @var Collection<array-key, string> $distUuids */
        $distUuids = $distContacts->map(function (ContactDto $contact) {
            return $this->sync->backend->getUuid($contact->uri);
        });

        /** @var Collection<array-key, string> $added */
        $addedUuids = collect($added)->map(function ($uri) {
            return $this->sync->backend->getUuid($uri);
        });

        return collect($localContacts)
            ->filter(function (Contact $contact) use ($distUuids, $addedUuids) {
                return ! $distUuids->contains($contact->uuid)
                    && ! $addedUuids->contains($contact->uuid);
            })->map(function (Contact $contact): PushVCard {
                $card = $this->sync->backend->prepareCard($contact);

                return new PushVCard($this->sync->subscription, new ContactPushDto($card['uri'], $card['etag'], $card['carddata'], 2));
            });
    }
}
