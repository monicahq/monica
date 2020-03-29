<?php

namespace App\Services\DavClient;

use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use App\Services\BaseService;
use GuzzleHttp\Promise\Promise;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use App\Models\Account\AddressBook;
use Illuminate\Support\Facades\Log;
use App\Services\DavClient\Dav\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Models\Account\AddressBookSubscription;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class SynchronizeAddressBook extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer|exists:users,id',
            'addressbook_subscription_id' => 'required|integer|exists:addressbook_subscriptions,id',
        ];
    }

    /**
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data, GuzzleClient $httpClient = null)
    {
        $this->validate($data);

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        $subscription = AddressBookSubscription::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_subscription_id']);

        $backend = new CardDAVBackend($user);

        try {
            $client = $this->getClient($subscription, $httpClient);

            $localChanges = $backend->getChangesForAddressBook($subscription->addressbook->addressBookId, $subscription->localSyncToken, 1);

            $this->distantChanges($subscription, $client, $backend)
            ->then(function ($changes) use ($subscription, $client, $backend, $localChanges) {

                $this->getContacts($subscription, $client, $backend, $changes);
                return $this->pushContacts($subscription->addressbook, $client, $backend, $changes, $localChanges);

            })
            ->wait();

        } catch (ClientException $e) {
            //$r = $e->getResponse();
            //$s = (string) $r->getBody();
            Log::error(__CLASS__.' execute: '.$e->getMessage(), $e);
        }
    }

    private function distantChanges(AddressBookSubscription $subscription, Client $client, CardDAVBackend $backend)
    {
        $addressBookId = $subscription->addressbook->addressBookId;
        return $this->getContactsList($subscription, $client)
          ->then(function ($collection) use ($addressBookId, $backend) {
            return collect($collection)
              ->filter(function ($contact) {
                return isset($contact[200]) && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard');
            })->filter(function ($contact, $href) use ($addressBookId, $backend) {
                $card = $backend->getCard($addressBookId, $href);
                return $card === false || $card['etag'] !== $contact[200]['{DAV:}getetag'];
            })->map(function ($contact, $href) {
                return [
                    'href' => $href,
                    'etag' => $contact[200]['{DAV:}getetag'],
                ];
            });
        });
    }

    private function getContacts(AddressBookSubscription $subscription, Client $client, CardDAVBackend $backend, Collection $refresh)
    {
        if (Arr::get($subscription->capabilities, 'addressbookMultiget', false)) {
            $refreshContacts = $this->refreshMultigetContacts($subscription, $client, $refresh);
        } else {
            $refreshContacts = $this->refreshSimpleGetContacts($client, $refresh);
        }

        $addressbook = $subscription->addressbook;
        return $refreshContacts->then(function ($contacts) use ($backend, $addressbook) {
            foreach ($contacts as $contact)
            {
                $newtag = $backend->updateCard($addressbook->addressBookId, $contact['href'], $contact['vcard']);

                if ($newtag != $contact['etag']) {
                    Log::warning(__CLASS__.' getContacts: wrong etag. Expected '.$contact['etag'].', get '.$newtag);
                }
            }
        });
    }

    private function refreshMultigetContacts(AddressBookSubscription $subscription, Client $client, Collection $refresh): PromiseInterface
    {
        $addressDataAttributes = Arr::get($subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        $hrefs = $refresh->pluck('href');

        return $client->addressbookMultigetAsync('', [
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
                ->map(function($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                        'vcard' => $contact[200]['{'.CardDAVPlugin::NS_CARDDAV.'}address-data'],
                    ];
                });
        });
    }

    private function refreshSimpleGetContacts(Client $client, Collection $contacts): PromiseInterface
    {
        $requests = $contacts->map(function ($uri) {
            return new Request('GET', $uri);
        })->toArray();

        return $client->requestPool($requests, [
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

    private function pushContacts(AddressBook $addressbook, Client $client, CardDAVBackend $backend, Collection $changes, array $localChanges): PromiseInterface
    {
        $requestsChanges = $this->pushChangedContacts($addressbook, $backend, $changes, $localChanges['modified']);
        $requestsAdded = $this->pushAddedContacts($addressbook, $backend, $localChanges['added']);

        $requests = $requestsChanges->union($requestsAdded);

        $urls = $requests->pluck('request')->toArray();

        return $client->requestPool($urls, [
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

    private function pushAddedContacts(AddressBook $addressbook, CardDAVBackend $backend, array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
          ->map(function ($uri) use ($addressbook, $backend) {
            return tap($backend->getCard($addressbook->addressBookId, $uri), function ($card) use ($uri) {
                $card['uri'] = $uri;
            });
        })->map(function ($contact) {
            $contact['request'] = new Request('PUT', $contact['uri'], [], $contact['carddata']);
            return $contact;
        });
    }

    private function pushChangedContacts(AddressBook $addressbook, CardDAVBackend $backend, Collection $changes, array $contacts): Collection
    {
        $refreshIds = $changes->pluck('href');

        // We don't push contact that have just been updated
        return collect($contacts)
          ->reject(function ($uri) use ($refreshIds, $backend) {
            $uuid = $backend->getUuid($uri);
            return $refreshIds->contains($uuid);
        })->map(function ($uri) use ($addressbook, $backend) {
            return tap($backend->getCard($addressbook->addressBookId, $uri), function ($card) use ($uri) {
                $card['uri'] = $uri;
            });
        })->map(function ($contact) {
            $contact['request'] = new Request('PUT', $contact['uri'], ['If-Match' => '*'], $contact['carddata']);
            return $contact;
        });
    }

    /**
     * @return PromiseInterface<array>
     */
    private function getContactsList(AddressBookSubscription $subscription, Client $client): PromiseInterface
    {
        // With sync-collection
        if (Arr::get($subscription->capabilities, 'syncCollection', false)) {

            $syncToken = $subscription->syncToken ?? '';

            // get the current distant syncToken
            $distantSyncToken = $client->getProperty('{DAV:}sync-token');

            if ($syncToken == $distantSyncToken) {
                // no change at all
                return new Promise(function () {
                    return [];
                });
            }

            // get sync
            return $client->syncCollectionAsync('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], $syncToken)->then(function ($collection) use ($subscription) {
                // save the new syncToken as current one
                $subscription->syncToken = $collection['synctoken'];
                $subscription->save();

                return $collection;
            });
        } else {

            // synchronisation
            return $client->propFindAsync('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }
    }

    private function getClient(AddressBookSubscription $subscription, GuzzleClient $client = null) : Client
    {
        return new Client([
            'base_uri' => $subscription->uri,
            'username' => $subscription->username,
            'password' => $subscription->password,
        ], $client);
    }
}
