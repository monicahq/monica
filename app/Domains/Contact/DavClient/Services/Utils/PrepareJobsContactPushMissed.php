<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use App\Models\Contact;
use Illuminate\Support\Collection;

class PrepareJobsContactPushMissed
{
    use HasSubscription;

    /**
     * Push contacts to the distant server.
     *
     * @param  Collection<array-key,Collection<array-key,string>>  $localChanges
     * @param  Collection<array-key,ContactDto>  $distContacts
     * @param  Collection<array-key,Contact>  $localContacts
     */
    public function execute(Collection $localChanges, Collection $distContacts, Collection $localContacts): Collection
    {
        $changes = app(PrepareJobsContactPush::class)
            ->withSubscription($this->subscription)
            ->execute($localChanges);

        $missings = $this->preparePushMissedContacts($localChanges->get('added', collect()), $distContacts, $localContacts);

        return $changes
            ->union($missings);
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param  Collection<array-key,string>  $added
     * @param  Collection<array-key,ContactDto>  $distContacts
     * @param  Collection<array-key,Contact>  $localContacts
     */
    private function preparePushMissedContacts(Collection $added, Collection $distContacts, Collection $localContacts): Collection
    {
        $distUuids = $distContacts->map(fn (ContactDto $contact): string => $this->backend()->getUuid($contact->uri));
        $addedUuids = $added->map(fn (string $uri): string => $this->backend()->getUuid($uri));

        return $localContacts
            ->filter(fn (Contact $contact): bool => ! $distUuids->contains($contact->id)
                && ! $addedUuids->contains($contact->id)
            )
            ->map(function (Contact $contact): PushVCard {
                $card = $this->backend()->prepareCard($contact);

                return new PushVCard($this->subscription,
                    $card['uri'],
                    $contact->distant_etag,
                    $card['carddata'],
                    $contact->id,
                    $contact->distant_etag !== null ? PushVCard::MODE_MATCH_ANY : PushVCard::MODE_MATCH_NONE
                );
            });
    }
}
