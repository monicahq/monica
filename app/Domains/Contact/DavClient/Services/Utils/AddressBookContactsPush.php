<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactPushDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AddressBookContactsPush
{
    use HasSubscription;

    /**
     * Push contacts to the distant server.
     *
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array<array-key, string>|null  $localChanges
     */
    public function execute(Collection $changes, ?array $localChanges): Collection
    {
        $changes = $this->preparePushChangedContacts($changes, Arr::get($localChanges, 'modified', []));
        $added = $this->preparePushAddedContacts(Arr::get($localChanges, 'added', []));
        $deleted = $this->prepareDeletedContacts(Arr::get($localChanges, 'deleted', []));

        return $changes
            ->union($added)
            ->union($deleted)
            ->filter();
    }

    /**
     * Get list of requests to push new contacts.
     */
    private function preparePushAddedContacts(array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
            ->map(function (string $uri): ?PushVCard {
                $card = $this->backend()->getCard($this->subscription->vault->name, $uri);

                return $card === false
                    ? null
                    : new PushVCard($this->subscription,
                        new ContactPushDto(
                            $uri,
                            $card['distant_etag'],
                            $card['carddata'],
                            $card['contact_id']
                        )
                    );
            });
    }

    /**
     * Get list of requests to delete contacts.
     */
    private function prepareDeletedContacts(array $contacts): Collection
    {
        // All removed contact must be deleted
        return collect($contacts)
            ->map(fn (string $uri): DeleteVCard => new DeleteVCard($this->subscription, $uri));
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param  Collection<array-key, ContactDto>  $changes
     */
    private function preparePushChangedContacts(Collection $changes, array $contacts): Collection
    {
        $refreshIds = $changes->map(fn (ContactDto $contact) => $this->backend()->getUuid($contact->uri));

        // We don't push contact that have just been pulled
        return collect($contacts)
            ->reject(fn (string $uri): bool => $refreshIds->contains($this->backend()->getUuid($uri))
            )->map(function (string $uri): ?PushVCard {
                $card = $this->backend()->getCard($this->subscription->vault->name, $uri);

                return $card === false
                    ? null
                    : new PushVCard($this->subscription,
                        new ContactPushDto(
                            $uri,
                            $card['distant_etag'],
                            $card['carddata'],
                            $card['contact_id'],
                            $card['distant_etag'] !== null ? ContactPushDto::MODE_MATCH_ETAG : ContactPushDto::MODE_MATCH_ANY
                        )
                    );
            });
    }
}
