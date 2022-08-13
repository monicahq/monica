<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Bus\Batch;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Traits\WithSyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;
use App\Services\DavClient\Utils\Model\ContactDeleteDto;
use App\Services\DavClient\UpdateSubscriptionLocalSyncToken;

class AddressBookSynchronizer
{
    use HasCapability, WithSyncDto;

    /**
     * Sync the address book.
     *
     * @return void
     */
    public function execute(SyncDto $sync, bool $force = false)
    {
        $this->sync = $sync;

        $force
            ? $this->forcesync()
            : $this->sync();
    }

    /**
     * Sync the address book.
     */
    private function sync()
    {
        // Get changes to sync
        $localChanges = $this->backend()->getChangesForAddressBook($this->sync->addressBookName(), (string) $this->sync->subscription->localSyncToken, 1);

        // Get distant changes to sync
        $changes = $this->getDistantChanges();

        // Get distant contacts
        $batch = app(AddressBookContactsUpdater::class)
            ->execute($this->sync, $changes);

        if (! $this->sync->subscription->readonly) {
            $batch->union(
                app(AddressBookContactsPush::class)
                    ->execute($this->sync, $changes, $localChanges)
            );
        }

        $accountId = $this->sync->subscription->account_id;
        $subscriptionId = $this->sync->subscription->id;
        Bus::batch($batch)
            ->then(function (Batch $batch) use ($accountId, $subscriptionId) {
                app(UpdateSubscriptionLocalSyncToken::class)->execute([
                    'account_id' => $accountId,
                    'addressbook_subscription_id' => $subscriptionId,
                ]);
            })
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Sync the address book.
     */
    private function forcesync()
    {
        $backend = $this->backend();

        // Get changes to sync
        $localChanges = $backend->getChangesForAddressBook($this->sync->addressBookName(), (string) $this->sync->subscription->localSyncToken, 1);

        // Get current list of contacts
        $localContacts = $backend->getObjects($this->sync->addressBookName());

        // Get distant changes to sync
        $distContacts = $this->getAllContactsEtag();

        // Get missed contacts
        $batch = app(AddressBookContactsUpdaterMissed::class)
                    ->execute($this->sync, $localContacts, $distContacts);

        if (! $this->sync->subscription->readonly) {
            $batch->union(
                app(AddressBookContactsPushMissed::class)
                    ->execute($this->sync, $localChanges, $distContacts, $localContacts)
            );
        }

        $accountId = $this->sync->subscription->account_id;
        $subscriptionId = $this->sync->subscription->id;
        Bus::batch($batch)
            ->then(function (Batch $batch) use ($accountId, $subscriptionId) {
                app(UpdateSubscriptionLocalSyncToken::class)->execute([
                    'account_id' => $accountId,
                    'addressbook_subscription_id' => $subscriptionId,
                ]);
            })
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Get distant changes to sync.
     *
     * @return Collection
     */
    private function getDistantChanges(): Collection
    {
        $etags = collect($this->getDistantEtags());
        $contacts = $etags->filter(function ($contact, $href): bool {
            return $this->filterDistantContacts($contact, $href);
        })
            ->map(function (array $contact, string $href): ContactDto {
                return new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag'));
            });

        $deleted = $etags->filter(function ($contact): bool {
            return is_array($contact) && $contact['status'] === '404';
        })
            ->map(function (array $contact, string $href): ContactDto {
                return new ContactDeleteDto($href);
            });

        return $contacts->union($deleted);
    }

    /**
     * Filter contacts to only return vcards type and new contacts or contacts with matching etags.
     *
     * @param  mixed  $contact
     * @param  string  $href
     * @return bool
     */
    private function filterDistantContacts($contact, $href): bool
    {
        // only return vcards
        if (! is_array($contact) || ! Str::contains(Arr::get($contact, 'properties.200.{DAV:}getcontenttype'), 'text/vcard')) {
            return false;
        }

        // only new contact or contact with etag that match
        $card = $this->backend()->getCard($this->sync->addressBookName(), $href);

        return $card === false || $card['etag'] !== Arr::get($contact, 'properties.200.{DAV:}getetag');
    }

    /**
     * Get refreshed etags.
     *
     * @return array
     */
    private function getDistantEtags(): array
    {
        if ($this->hasCapability('syncCollection')) {
            // With sync-collection
            return $this->callSyncCollectionWhenNeeded();
        } else {
            // With PROPFIND
            return $this->sync->propFind([
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }
    }

    /**
     * Make sync-collection request if sync-token has changed.
     *
     * @return array
     */
    private function callSyncCollectionWhenNeeded(): array
    {
        // get the current distant syncToken
        $distantSyncToken = $this->sync->getProperty('{DAV:}sync-token');

        if (($this->sync->subscription->syncToken ?? '') === $distantSyncToken) {
            // no change at all
            return [];
        }

        return $this->callSyncCollection();
    }

    /**
     * Make sync-collection request.
     *
     * @return array
     */
    private function callSyncCollection(): array
    {
        $syncToken = $this->sync->subscription->syncToken ?? '';

        // get sync
        $collection = $this->sync->syncCollection([
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
        ], $syncToken);

        // save the new syncToken as current one
        if ($newSyncToken = Arr::get($collection, 'synctoken')) {
            $this->sync->subscription->syncToken = $newSyncToken;
            $this->sync->subscription->save();
        }

        return $collection;
    }

    /**
     * Get all contacts etag.
     *
     * @return Collection
     */
    private function getAllContactsEtag(): Collection
    {
        if (! $this->hasCapability('addressbookQuery')) {
            return collect();
        }

        $data = $this->sync->addressbookQuery('{DAV:}getetag');
        $data = collect($data);

        $updated = $data->filter(function ($contact): bool {
            return is_array($contact) && $contact['status'] === '200';
        })
            ->map(function (array $contact, string $href): ContactDto {
                return new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag'));
            });
        $deleted = $data->filter(function ($contact): bool {
            return is_array($contact) && $contact['status'] === '404';
        })
            ->map(function (array $contact, string $href): ContactDto {
                return new ContactDeleteDto($href);
            });

        return $updated->union($deleted);
    }
}
