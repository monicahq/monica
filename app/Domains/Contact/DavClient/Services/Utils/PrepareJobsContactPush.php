<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Support\Collection;

class PrepareJobsContactPush
{
    use HasSubscription;

    /**
     * Push contacts to the distant server.
     *
     * @param  Collection<array-key,Collection<array-key,string>>  $localChanges
     * @param  Collection<array-key,ContactDto>|null  $changes
     */
    public function execute(Collection $localChanges, ?Collection $changes = null): Collection
    {
        $modified = $this->preparePushChangedContacts($localChanges->get('modified', collect()), $changes ?? collect());
        $added = $this->preparePushAddedContacts($localChanges->get('added', collect()));
        $deleted = $this->prepareDeletedContacts($localChanges->get('deleted', collect()));

        return $modified
            ->merge($added)
            ->merge($deleted)
            ->filter();
    }

    /**
     * Get list of requests to push new contacts.
     *
     * @param  Collection<array-key,string>  $contacts
     */
    private function preparePushAddedContacts(Collection $contacts): Collection
    {
        // All added contact must be pushed
        return $this->filterContacts($contacts)
            ->map(function (string $uri): ?PushVCard {
                $card = $this->backend()->getCard($this->subscription->vault_id, $uri);

                return $card === false
                    ? null
                    : new PushVCard($this->subscription,
                        $uri,
                        $card['distant_etag'],
                        $card['carddata'],
                        $card['contact_id']
                    );
            });
    }

    /**
     * Get list of requests to delete contacts.
     *
     * @param  Collection<array-key,string>  $contacts
     */
    private function prepareDeletedContacts(Collection $contacts): Collection
    {
        // All removed contact must be deleted
        return $this->filterContacts($contacts)
            ->map(fn (string $uri): DeleteVCard => new DeleteVCard($this->subscription, $uri));
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param  Collection<array-key,string>  $contacts
     * @param  Collection<array-key,ContactDto>  $changes
     */
    private function preparePushChangedContacts(Collection $contacts, Collection $changes): Collection
    {
        $refreshIds = $changes->map(fn (ContactDto $contact): string => $this->backend()->getUuid($contact->uri));

        // We don't push contact that have just been pulled
        return $this->filterContacts($contacts)
            ->reject(fn (string $uri): bool => $refreshIds->contains($this->backend()->getUuid($uri)))
            ->map(function (string $uri): ?PushVCard {
                $card = $this->backend()->getCard($this->subscription->vault_id, $uri);

                if ($card === false) {
                    return null;
                }

                return new PushVCard($this->subscription,
                    $uri,
                    $card['distant_etag'],
                    $card['carddata'],
                    $card['contact_id'],
                    $card['distant_etag'] !== null ? PushVCard::MODE_MATCH_ETAG : PushVCard::MODE_MATCH_ANY
                );
            });
    }

    /**
     * Filter list of contacts.
     *
     * @param  Collection<array-key,string>  $contacts
     */
    private function filterContacts(Collection $contacts): Collection
    {
        return $contacts->reject(fn (string $uri): bool => $this->getContactInVault() === $this->backend()->getUuid($uri)
        );
    }

    private function getContactInVault(): ?string
    {
        $entry = $this->subscription->user->vaults()
            ->wherePivot('vault_id', $this->subscription->vault_id)
            ->first();

        $pivot = optional($entry)->pivot;

        return optional($pivot)->contact_id;
    }
}
