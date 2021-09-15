<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Str;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use App\Services\DavClient\Utils\Model\SyncDto;
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

        $force ? $this->forcesync() : $this->sync();
    }

    /**
     * Sync the address book.
     */
    private function sync()
    {
        // Get changes to sync
        $localChanges = $this->sync->backend->getChangesForAddressBook($this->sync->subscription->addressbook->name, $this->sync->subscription->localSyncToken, 1);

        // Get distant changes to sync
        $this->getDistantChanges()
            ->then(function ($changes) {
                // Get distant contacts
                app(AddressBookContactsUpdater::class)
                    ->execute($this->sync, $changes);

                return $changes;
            })
            ->then(function ($changes) use ($localChanges) {
                if (! $this->sync->subscription->readonly) {
                    app(AddressBookContactsPusher::class)
                        ->execute($this->sync, $changes, $localChanges);
                }
            })
            ->wait();

        $token = $this->sync->backend->getCurrentSyncToken($this->sync->subscription->addressbook->name);

        $this->sync->subscription->localSyncToken = $token->id;
        $this->sync->subscription->save();
    }

    /**
     * Sync the address book.
     */
    private function forcesync()
    {
        // Get changes to sync
        $localChanges = $this->sync->backend->getChangesForAddressBook($this->sync->subscription->addressbook->name, $this->sync->subscription->localSyncToken, 1);

        // Get actual list of contacts
        $localContacts = $this->sync->backend->getObjects($this->sync->subscription->addressbook->name);

        // Get distant changes to sync
        $this->getAllContactsEtag()
            ->then(function ($distContacts) use ($localContacts) {
                // Get missed contacts
                app(AddressBookContactsUpdaterMissed::class)
                    ->execute($this->sync, $localContacts, $distContacts);

                return $distContacts;
            })
            ->then(function ($distContacts) use ($localChanges, $localContacts) {
                if (! $this->sync->subscription->readonly) {
                    app(AddressBookContactsPusher::class)
                        ->execute($this->sync, collect(), $localChanges, $distContacts, $localContacts);
                }
            })
            ->wait();
    }

    /**
     * Get distant changes to sync.
     *
     * @return PromiseInterface
     */
    private function getDistantChanges(): PromiseInterface
    {
        return $this->getDistantEtags()
          ->then(function ($collection) {
              return collect($collection)
                ->filter(function ($contact) {
                    // only return vcards
                    return isset($contact[200])
                        && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard');
                })->filter(function ($contact, $href) {
                    // only new contact or contact with etag that match
                    $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $href);

                    return $card === false || $card['etag'] !== $contact[200]['{DAV:}getetag'];
                })->map(function ($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                    ];
                });
          });
    }

    /**
     * Get refreshed etags.
     *
     * @return PromiseInterface
     */
    private function getDistantEtags(): PromiseInterface
    {
        if ($this->hasCapability('syncCollection')) {
            // With sync-collection
            return $this->callSyncCollection();
        } else {
            // With PROPFIND
            return $this->sync->client->propFindAsync('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }
    }

    /**
     * Make sync-collection request.
     *
     * @return PromiseInterface
     */
    private function callSyncCollection(): PromiseInterface
    {
        // With sync-collection
        $syncToken = $this->sync->subscription->syncToken ?? '';

        // get the current distant syncToken
        $distantSyncToken = $this->sync->client->getProperty('{DAV:}sync-token');

        if ($syncToken == $distantSyncToken) {
            // no change at all
            return $this->emptyPromise();
        }

        // get sync
        return $this->sync->client->syncCollectionAsync('', [
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
        ], $syncToken)->then(function ($collection) {
            // save the new syncToken as current one
            if (array_key_exists('synctoken', $collection)) {
                $this->sync->subscription->syncToken = $collection['synctoken'];
                $this->sync->subscription->save();
            }

            return $collection;
        });
    }

    /**
     * Get all contacts etag.
     *
     * @return PromiseInterface
     */
    private function getAllContactsEtag(): PromiseInterface
    {
        if (! $this->hasCapability('addressbookQuery')) {
            return $this->emptyPromise();
        }

        return $this->sync->client->addressbookQueryAsync('', [
            '{DAV:}getetag',
        ])->then(function ($datas) {
            return collect($datas)
                ->filter(function ($contact) {
                    return isset($contact[200]);
                })
                ->map(function ($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                    ];
                });
        });
    }

    /**
     * Get an empty Promise.
     *
     * @return PromiseInterface
     */
    private function emptyPromise(): PromiseInterface
    {
        $promise = new Promise(function () use (&$promise) {
            $promise->resolve([]);
        });

        return $promise;
    }
}
