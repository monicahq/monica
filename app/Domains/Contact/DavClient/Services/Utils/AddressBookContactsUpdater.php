<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\DeleteMultipleVCard;
use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Jobs\GetVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDeleteDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasCapability;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Support\Collection;

class AddressBookContactsUpdater
{
    use HasCapability, HasSubscription;

    /**
     * Update local contacts.
     *
     * @param  Collection<array-key, ContactDto>  $refresh
     */
    public function execute(Collection $refresh): Collection
    {
        return $this->hasCapability('addressbookMultiget')
            ? $this->refreshMultigetContacts($refresh)
            : $this->refreshSimpleGetContacts($refresh);
    }

    /**
     * Get contacts data with addressbook-multiget request.
     *
     * @param  Collection<array-key, ContactDto>  $refresh
     */
    private function refreshMultigetContacts(Collection $refresh): Collection
    {
        $refresh = $refresh->groupBy(fn ($item): string => $item instanceof ContactDeleteDto ? 'deleted' : 'updated')
            ->map(fn (Collection $items): array => $items->pluck('uri')->toArray());

        return collect([
            new GetMultipleVCard($this->subscription, $refresh->get('updated', [])),
            new DeleteMultipleVCard($this->subscription, $refresh->get('deleted', [])),
        ]);
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection<array-key, ContactDto>  $refresh
     */
    private function refreshSimpleGetContacts(Collection $refresh): Collection
    {
        return $refresh
            ->map(fn (ContactDto $contact) => $contact instanceof ContactDeleteDto
                ? new DeleteVCard($this->subscription, $contact->uri)
                : new GetVCard($this->subscription, $contact)
            );
    }
}
