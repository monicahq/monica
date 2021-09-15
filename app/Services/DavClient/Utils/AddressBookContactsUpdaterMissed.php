<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Collection;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;

class AddressBookContactsUpdaterMissed
{
    use HasCapability;

    /**
     * @var SyncDto
     */
    private $sync;

    /**
     * Update local missed contacts.
     *
     * @param  SyncDto  $sync
     * @param  Collection  $localContacts
     * @param  Collection  $distContacts
     */
    public function execute(SyncDto $sync, Collection $localContacts, Collection $distContacts): void
    {
        $this->sync = $sync;

        $uuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(function ($contact) use ($uuids): bool {
            return $uuids->contains($this->sync->backend->getUuid($contact['href']));
        });

        app(AddressBookContactsUpdater::class)->execute($this->sync, $missed);
    }
}
