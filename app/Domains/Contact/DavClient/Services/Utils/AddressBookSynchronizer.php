<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Services\UpdateSubscriptionLocalSyncToken;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDeleteDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasCapability;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class AddressBookSynchronizer
{
    use HasCapability, HasSubscription;

    private DavClient $client;

    /**
     * Sync the address book.
     */
    public function execute(bool $force = false): void
    {
        $this->client = $this->subscription->getClient();

        $batch = $force
            ? $this->forcesync()
            : $this->sync();

        Bus::batch($batch)
            ->then(fn () => app(UpdateSubscriptionLocalSyncToken::class)->execute([
                'addressbook_subscription_id' => $this->subscription->id,
            ]))
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Sync the address book.
     */
    private function sync(): Collection
    {
        // Get changes to sync
        $localChanges = $this->backend()->getChangesForAddressBook($this->subscription->vault_id, (string) $this->subscription->localSyncToken, 1);

        // Get distant changes to sync
        $changes = $this->getDistantChanges();

        // Get distant contacts
        $batch = app(AddressBookContactsUpdater::class)
            ->withSubscription($this->subscription)
            ->execute($changes);

        if (! $this->subscription->readonly) {
            $batch->union(
                app(AddressBookContactsPush::class)
                    ->withSubscription($this->subscription)
                    ->execute($changes, $localChanges)
            );
        }

        return $batch;
    }

    /**
     * Sync the address book.
     */
    private function forcesync(): Collection
    {
        // Get changes to sync
        $localChanges = $this->backend()->getChangesForAddressBook($this->subscription->vault_id, (string) $this->subscription->localSyncToken, 1);

        // Get current list of contacts
        $localContacts = $this->backend()->getObjects($this->subscription->vault_id);

        // Get distant changes to sync
        $distContacts = $this->getAllContactsEtag();

        // Get missed contacts
        $batch = app(AddressBookContactsUpdaterMissed::class)
            ->withSubscription($this->subscription)
            ->execute($localContacts, $distContacts);

        if (! $this->subscription->readonly) {
            $batch->union(
                app(AddressBookContactsPushMissed::class)
                    ->withSubscription($this->subscription)
                    ->execute($localChanges, $distContacts, $localContacts)
            );
        }

        return $batch;
    }

    /**
     * Get distant changes to sync.
     */
    private function getDistantChanges(): Collection
    {
        $etags = $this->getDistantEtags();
        $data = collect($etags);

        $contacts = $data->filter(fn ($contact, $href): bool => $this->filterDistantContacts($contact, $href)
        )
            ->map(fn (array $contact, string $href): ContactDto => new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag'))
            );

        $deleted = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '404'
        )
            ->map(fn (array $contact, string $href): ContactDto => new ContactDeleteDto($href)
            );

        return $contacts->union($deleted);
    }

    /**
     * Filter contacts to only return vcards type and new contacts or contacts with matching etags.
     */
    private function filterDistantContacts(mixed $contact, string $href): bool
    {
        // only return vcards
        if (! is_array($contact) || ! Str::contains(Arr::get($contact, 'properties.200.{DAV:}getcontenttype'), 'text/vcard')) {
            return false;
        }

        // only new contact or contact with etag that match
        $card = $this->backend()->getCard($this->subscription->vault->name, $href);

        return $card === false || $card['etag'] !== Arr::get($contact, 'properties.200.{DAV:}getetag');
    }

    /**
     * Get refreshed etags.
     */
    private function getDistantEtags(): array
    {
        return $this->hasCapability('syncCollection')
            // With sync-collection
            ? $this->callSyncCollectionWhenNeeded()
            // With PROPFIND
            : $this->client->propFind([
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
    }

    /**
     * Make sync-collection request if sync-token has changed.
     */
    private function callSyncCollectionWhenNeeded(): array
    {
        // get the current distant syncToken
        $distantSyncToken = $this->client->getProperty('{DAV:}sync-token');

        if (($this->subscription->syncToken ?? '') === $distantSyncToken) {
            // no change at all
            return [];
        }

        return $this->callSyncCollection();
    }

    /**
     * Make sync-collection request.
     */
    private function callSyncCollection(): array
    {
        $syncToken = $this->subscription->syncToken ?? '';

        // get sync
        $collection = $this->client->syncCollection([
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
        ], $syncToken);

        // save the new syncToken as current one
        if ($newSyncToken = Arr::get($collection, 'synctoken')) {
            $this->subscription->syncToken = $newSyncToken;
            $this->subscription->save();
        }

        return $collection;
    }

    /**
     * Get all contacts etag.
     */
    private function getAllContactsEtag(): Collection
    {
        if (! $this->hasCapability('addressbookQuery')) {
            return collect();
        }

        $query = $this->client->addressbookQuery('{DAV:}getetag');
        $data = collect($query);

        $updated = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '200'
        )
            ->map(fn (array $contact, string $href): ContactDto => new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag'))
            );
        $deleted = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '404'
        )
            ->map(fn (array $contact, string $href): ContactDto => new ContactDeleteDto($href)
            );

        return $updated->union($deleted);
    }
}
