<?php

namespace App\Services\DavClient\Utils;

use App\Jobs\Dav\GetVCard;
use App\Jobs\Dav\DeleteVCard;
use App\Jobs\Dav\GetMultipleVCard;
use Illuminate\Support\Collection;
use App\Jobs\Dav\DeleteMultipleVCard;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Traits\WithSyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;
use App\Services\DavClient\Utils\Model\ContactDeleteDto;

class AddressBookContactsUpdater
{
    use HasCapability, WithSyncDto;

    /**
     * Update local contacts.
     *
     * @param  SyncDto  $sync
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $refresh
     * @return Collection
     */
    public function execute(SyncDto $sync, Collection $refresh): Collection
    {
        $this->sync = $sync;

        return $this->hasCapability('addressbookMultiget')
            ? $this->refreshMultigetContacts($refresh)
            : $this->refreshSimpleGetContacts($refresh);
    }

    /**
     * Get contacts data with addressbook-multiget request.
     *
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $refresh
     * @return Collection
     */
    private function refreshMultigetContacts(Collection $refresh): Collection
    {
        $updated = $refresh
            ->filter(function ($item): bool {
                return ! ($item instanceof ContactDeleteDto);
            })
            ->pluck('uri')->toArray();

        $deleted = $refresh
            ->filter(function ($item): bool {
                return $item instanceof ContactDeleteDto;
            })
            ->pluck('uri')->toArray();

        return collect([
            new GetMultipleVCard($this->sync->subscription, $updated),
            new DeleteMultipleVCard($this->sync->subscription, $deleted),
        ]);
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $refresh
     * @return Collection
     */
    private function refreshSimpleGetContacts(Collection $refresh): Collection
    {
        return $refresh
            ->map(function (ContactDto $contact) {
                if ($contact instanceof ContactDeleteDto) {
                    return new DeleteVCard($this->sync->subscription, $contact->uri);
                } else {
                    return new GetVCard($this->sync->subscription, $contact);
                }
            });
    }
}
