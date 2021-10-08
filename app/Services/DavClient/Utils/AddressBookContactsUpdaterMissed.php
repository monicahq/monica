<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Collection;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Traits\WithSyncDto;

class AddressBookContactsUpdaterMissed
{
    use WithSyncDto;

    /**
     * Update local missed contacts.
     *
     * @param  SyncDto  $sync
     * @param  Collection<array-key, \App\Models\Contact\Contact>  $localContacts
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $distContacts
     * @return Collection
     */
    public function execute(SyncDto $sync, Collection $localContacts, Collection $distContacts): Collection
    {
        $this->sync = $sync;

        $uuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(function (ContactDto $contact) use ($uuids): bool {
            return $uuids->contains($this->backend()->getUuid($contact->uri));
        });

        return app(AddressBookContactsUpdater::class)
            ->execute($this->sync, $missed);
    }
}
