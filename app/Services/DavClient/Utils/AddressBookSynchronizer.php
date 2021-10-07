<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Bus\Batch;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Traits\HasCapability;

class AddressBookSynchronizer
{
    use HasCapability;

    /**
     * @var SyncDto
     */
    private $sync;

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
        $localChanges = $this->sync->backend->getChangesForAddressBook($this->sync->addressBookName(), (string) $this->sync->subscription->localSyncToken, 1);

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

        Bus::batch($batch)
            ->then(function (Batch $batch) {
                $token = $this->sync->backend->getCurrentSyncToken($this->sync->addressBookName());

                $this->sync->subscription->localSyncToken = $token->id;
                $this->sync->subscription->save();
            })
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Sync the address book.
     */
    private function forcesync()
    {
        // Get changes to sync
        $localChanges = $this->sync->backend->getChangesForAddressBook($this->sync->addressBookName(), (string) $this->sync->subscription->localSyncToken, 1);

        // Get current list of contacts
        $localContacts = $this->sync->backend->getObjects($this->sync->addressBookName());

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

        Bus::batch($batch)
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
        return collect($this->getDistantEtags())
            ->filter(function ($contact, $href): bool {
                return $this->filterDistantContacts($contact, $href);
            })
            ->map(function ($contact, $href): ContactDto {
                return new ContactDto($href, Arr::get($contact, '200.{DAV:}getetag'));
            });
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
        if (! is_array($contact) || ! Str::contains(Arr::get($contact, '200.{DAV:}getcontenttype'), 'text/vcard')) {
            return false;
        }

        // only new contact or contact with etag that match
        $card = $this->sync->backend->getCard($this->sync->addressBookName(), $href);

        return $card === false || $card['etag'] !== Arr::get($contact, '200.{DAV:}getetag');
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

        $datas = $this->sync->addressbookQuery('{DAV:}getetag');

        return collect($datas)
            ->filter(function ($contact) {
                return isset($contact[200]);
            })
            ->map(function ($contact, $href): ContactDto {
                return new ContactDto($href, Arr::get($contact, '200.{DAV:}getetag'));
            });
    }
}
