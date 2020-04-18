<?php

namespace App\Http\Controllers\DAVClient;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\Promise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Http\Controllers\DAVClient\Dav\Client;
use App\Models\Account\AddressBookSubscription;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class AddressBookSynchronizer
{
    /**
     * @var AddressBookSubscription
     */
    private $subscription;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CardDAVBackend
     */
    private $backend;

    public function __construct(AddressBookSubscription $subscription, Client $client, CardDAVBackend $backend)
    {
        $this->subscription = $subscription;
        $this->client = $client;
        $this->backend = $backend;
    }

    /**
     * Sync the address book.
     */
    public function sync()
    {
        // Get changes to sync
        $localChanges = $this->backend->getChangesForAddressBook($this->subscription->addressbook->name, $this->subscription->localSyncToken, 1);

        // Get distant changes to sync
        $this->getDistantChanges()
            ->then(function ($changes) {
                // Get distant contacts
                $this->updateContacts($changes);

                return $changes;
            })
            ->then(function ($changes) use ($localChanges) {
                if ($this->subscription->readonly) {
                    return;
                }

                return $this->pushContacts($changes, $localChanges);
            })
            ->wait();

        $token = $this->backend->getCurrentSyncToken($this->subscription->addressbook->name, false);

        $this->subscription->localSyncToken = $token->id;
        $this->subscription->save();
    }

    /**
     * Sync the address book.
     */
    public function forcesync()
    {
        // Get changes to sync
        $localChanges = $this->backend->getChangesForAddressBook($this->subscription->addressbook->name, $this->subscription->localSyncToken, 1);
        // Get actual list of contacts
        $localContacts = $this->backend->getObjects($this->subscription->addressbook->name);

        // Get distant changes to sync
        $this->getAllContactsEtag()
            ->then(function ($distContacts) use ($localContacts) {
                // Get missed contacts
                $this->updateMissedContacts($localContacts, $distContacts);

                return $distContacts;
            })
            ->then(function ($distContacts) use ($localChanges, $localContacts) {
                if ($this->subscription->readonly) {
                    return;
                }

                return $this->pushContacts(collect(), $localChanges, $distContacts, $localContacts);
            })
            ->wait();
    }

    /**
     * Get distant changes to sync.
     *
     * @return PromiseInterface<array>
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
                    $card = $this->backend->getCard($this->subscription->addressbook->name, $href);

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
     * Update local contacts.
     *
     * @param Collection $refresh
     */
    private function updateContacts(Collection $refresh)
    {
        if (Arr::get($this->subscription->capabilities, 'addressbookMultiget', false)) {
            $refreshContacts = $this->refreshMultigetContacts($refresh);
        } else {
            $refreshContacts = $this->refreshSimpleGetContacts($refresh);
        }

        return $refreshContacts->then(function ($contacts) {
            foreach ($contacts as $contact) {
                $newtag = $this->backend->updateCard($this->subscription->addressbook->name, $contact['href'], $contact['vcard']);

                if ($newtag != $contact['etag']) {
                    Log::warning(__CLASS__.' getContacts: wrong etag. Expected '.$contact['etag'].', get '.$newtag);
                }
            }
        });
    }

    /**
     * Update local missed contacts.
     *
     * @param Collection $localContacts
     * @param Collection $distContacts
     */
    private function updateMissedContacts(Collection $localContacts, Collection $distContacts)
    {
        $localUuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(function ($contact) use ($localUuids) {
            return $localUuids->contains($this->backend->getUuid($contact['href']));
        });

        $this->updateContacts($missed);
    }

    /**
     * Get contacts data with addressbook-multiget request.
     *
     * @param Collection $refresh
     * @return PromiseInterface<array>
     */
    private function refreshMultigetContacts(Collection $refresh): PromiseInterface
    {
        $addressDataAttributes = Arr::get($this->subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        $hrefs = $refresh->pluck('href');

        return $this->client->addressbookMultigetAsync('', [
            '{DAV:}getetag',
            [
                'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
                'value' => null,
                'attributes' => $addressDataAttributes,
            ],
        ], $hrefs)->then(function ($datas) {
            return collect($datas)
                ->filter(function ($contact) {
                    return isset($contact[200]);
                })
                ->map(function ($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                        'vcard' => $contact[200]['{'.CardDAVPlugin::NS_CARDDAV.'}address-data'],
                    ];
                });
        });
    }

    /**
     * Get contacts data with request.
     *
     * @param Collection $contacts
     * @return PromiseInterface<array>
     */
    private function refreshSimpleGetContacts(Collection $contacts): PromiseInterface
    {
        $requests = $contacts->map(function ($uri) {
            return new Request('GET', $uri);
        })->toArray();

        return $this->client->requestPool($requests, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($contacts): array {
                if ($response->getStatusCode() === 200) {
                    Log::info(__CLASS__.' refreshSimpleGetContacts: GET '.$contacts[$index]['href']);

                    return [
                        'href' => $contacts[$index]['href'],
                        'etag' => $contacts[$index]['etag'],
                        'vcard' => $response->getBody()->detach(),
                    ];
                }

                return [];
            },
        ])->promise();
    }

    /**
     * Push contacts to the distant server.
     *
     * @param Collection $changes
     * @param array|null $localChanges
     * @param Collection|null $distContacts
     * @param Collection|null $localContacts
     * @return PromiseInterface
     */
    private function pushContacts(Collection $changes, ?array $localChanges, ?Collection $distContacts = null, ?Collection $localContacts = null): PromiseInterface
    {
        if (! $localChanges) {
            $localChanges = [
                'modified' => [],
                'added' => [],
            ];
        }
        $requestsChanges = $this->preparePushChangedContacts($changes, $localChanges['modified']);
        $requestsAdded = $this->preparePushAddedContacts($localChanges['added']);
        $requests = $requestsChanges->union($requestsAdded);

        if ($distContacts && $localContacts) {
            $requestsMissed = $this->preparePushMissedContacts($localChanges['added'], $distContacts, $localContacts);
            $requests = $requests->union($requestsMissed);
        }

        $urls = $requests->pluck('request')->toArray();

        return $this->client->requestPool($urls, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($requests) {
                Log::info(__CLASS__.' pushContacts: PUT '.$requests[$index]['uri']);
                $etags = $response->getHeader('Etag');
                if (count($etags) > 0 && $etags[0] !== $requests[$index]['etag']) {
                    Log::warning(__CLASS__.' pushContacts: wrong etag. Expected '.$requests[$index]['etag'].', get '.$etags[0]);
                }
            },
        ])->promise();
    }

    /**
     * Get list of requests to push new contacts.
     *
     * @param array $contacts
     * @return Collection
     */
    private function preparePushAddedContacts(array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
          ->map(function ($uri) {
              return tap($this->backend->getCard($this->subscription->addressbook->name, $uri), function ($card) use ($uri) {
                  $card['uri'] = $uri;
              });
          })->map(function ($contact) {
              $contact['request'] = new Request('PUT', $contact['uri'], [], $contact['carddata']);

              return $contact;
          });
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param Collection $changes
     * @param array $contacts
     * @return Collection
     */
    private function preparePushChangedContacts(Collection $changes, array $contacts): Collection
    {
        $refreshIds = $changes->pluck('href');

        // We don't push contact that have just been pulled
        return collect($contacts)
          ->reject(function ($uri) use ($refreshIds) {
              $uuid = $this->backend->getUuid($uri);

              return $refreshIds->contains($uuid);
          })->map(function ($uri) {
              return tap($this->backend->getCard($this->subscription->addressbook->name, $uri), function ($card) use ($uri) {
                  $card['uri'] = $uri;
              });
          })->map(function ($contact) {
              $contact['request'] = new Request('PUT', $contact['uri'], ['If-Match' => $contact['etag']], $contact['carddata']);

              return $contact;
          });
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param array $added
     * @param Collection $distContacts
     * @param Collection $localContacts
     * @return Collection
     */
    private function preparePushMissedContacts(array $added, Collection $distContacts, Collection $localContacts): Collection
    {
        $distContacts = $distContacts->map(function ($c) {
            return $this->backend->getUuid($c['href']);
        });
        $added = collect($added)->map(function ($c) {
            return $this->backend->getUuid($c);
        });

        return $localContacts
          ->filter(function ($contact) use ($distContacts, $added) {
              return ! $distContacts->contains($contact->uuid)
                && ! $added->contains($contact->uuid);
          })->map(function ($contact) {
              $data = $this->backend->prepareCard($contact);

              $data['request'] = new Request('PUT', $data['uri'], ['If-Match' => '*'], $data['carddata']);

              return $data;
          })
            ->values();
    }

    /**
     * Get refreshed etags.
     *
     * @return PromiseInterface<array>
     */
    private function getDistantEtags(): PromiseInterface
    {
        if (Arr::get($this->subscription->capabilities, 'syncCollection', false)) {
            // With sync-collection
            return $this->callSyncCollection();
        } else {
            // With PROPFIND
            return $this->client->propFindAsync('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }
    }

    /**
     * Make sync-collection request.
     *
     * @return PromiseInterface<array>
     */
    private function callSyncCollection(): PromiseInterface
    {
        // With sync-collection
        $syncToken = $this->subscription->syncToken ?? '';

        // get the current distant syncToken
        $distantSyncToken = $this->client->getProperty('{DAV:}sync-token');

        if ($syncToken == $distantSyncToken) {
            // no change at all
            return $this->emptyPromise();
        }

        // get sync
        return $this->client->syncCollectionAsync('', [
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
        ], $syncToken)->then(function ($collection) {
            // save the new syncToken as current one
            $this->subscription->syncToken = $collection['synctoken'];
            $this->subscription->save();

            return $collection;
        });
    }

    /**
     * Get all contacts etag.
     *
     * @return PromiseInterface<array>
     */
    private function getAllContactsEtag(): PromiseInterface
    {
        if (! Arr::get($this->subscription->capabilities, 'addressbookQuery', false)) {
            return $this->emptyPromise();
        }

        return $this->client->addressbookQueryAsync('', [
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
