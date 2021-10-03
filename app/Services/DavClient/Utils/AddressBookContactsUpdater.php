<?php

namespace App\Services\DavClient\Utils;

use App\Jobs\Dav\GetVCard;
use App\Jobs\Dav\GetMultipleVCard;
use Illuminate\Support\Collection;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;

class AddressBookContactsUpdater
{
    use HasCapability;

    /**
     * @var SyncDto
     */
    private $sync;

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
        $hrefs = $refresh->pluck('uri')->toArray();

        return collect([
            new GetMultipleVCard($this->sync->subscription, $hrefs),
        ]);
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $requests
     * @return Collection
     */
    private function refreshSimpleGetContacts(Collection $requests): Collection
    {
        return $requests->map(function ($contact): GetVCard {
            return new GetVCard($this->sync->subscription, $contact);
        });
    }
}
