<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Support\Collection;

class AddressBookContactsUpdaterMissed
{
    use HasSubscription;

    /**
     * Update local missed contacts.
     *
     * @param  Collection<array-key, \App\Models\Contact\Contact>  $localContacts
     * @param  Collection<array-key, \App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto>  $distContacts
     */
    public function execute(Collection $localContacts, Collection $distContacts): Collection
    {
        $uuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(fn (ContactDto $contact): bool => $uuids->contains($this->backend()->getUuid($contact->uri))
        );

        return app(AddressBookContactsUpdater::class)
            ->withSubscription($this->subscription)
            ->execute($missed);
    }
}
