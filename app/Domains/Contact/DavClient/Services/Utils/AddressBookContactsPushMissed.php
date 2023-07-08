<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactPushDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use App\Models\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AddressBookContactsPushMissed
{
    use HasSubscription;

    /**
     * Push contacts to the distant server.
     *
     * @param  array<array-key, string>|null  $localChanges
     * @param  Collection<array-key, ContactDto>  $distContacts
     * @param  Collection<array-key, Contact>  $localContacts
     */
    public function execute(?array $localChanges, Collection $distContacts, Collection $localContacts): Collection
    {
        $missings = $this->preparePushMissedContacts(Arr::get($localChanges, 'added', []), $distContacts, $localContacts);

        return app(AddressBookContactsPush::class)
            ->withSubscription($this->subscription)
            ->execute(collect(), $localChanges)
            ->union($missings);
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param  array<array-key, string>  $added
     * @param  Collection<array-key, ContactDto>  $distContacts
     * @param  Collection<array-key, Contact>  $localContacts
     */
    private function preparePushMissedContacts(array $added, Collection $distContacts, Collection $localContacts): Collection
    {
        $distUuids = $distContacts->map(fn (ContactDto $contact): string => $this->backend()->getUuid($contact->uri));
        $addedUuids = collect($added)->map(fn (string $uri): string => $this->backend()->getUuid($uri));

        return collect($localContacts)
            ->filter(fn (Contact $contact): bool => ! $distUuids->contains($contact->uuid)
                && ! $addedUuids->contains($contact->uuid)
            )
            ->map(function (Contact $contact): PushVCard {
                $card = $this->backend()->prepareCard($contact);

                return new PushVCard($this->subscription,
                    new ContactPushDto(
                        $card['uri'],
                        $contact->distant_etag,
                        $card['carddata'],
                        $contact->id,
                        ContactPushDto::MODE_MATCH_ANY
                    )
                );
            });
    }
}
