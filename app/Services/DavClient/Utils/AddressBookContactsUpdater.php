<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Traits\HasCapability;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\Model\SyncDto;

class AddressBookContactsUpdater
{
    use HasCapability;

    /**
     * @var SyncDto
     */
    private $sync;

    /**
     * Update local contacts.
     *
     * @param  SyncDto $sync
     * @param  Collection  $refresh
     */
    public function updateContacts(SyncDto $sync, Collection $refresh): void
    {
        $this->sync = $sync;

        $promise = $this->hasCapability('addressbookMultiget')
            ? $this->refreshMultigetContacts($refresh)
            : $this->refreshSimpleGetContacts($refresh);

        $promise->wait();
    }

    /**
     * Update local missed contacts.
     *
     * @param  SyncDto $sync
     * @param  Collection  $localContacts
     * @param  Collection  $distContacts
     */
    public function updateMissedContacts(SyncDto $sync, Collection $localContacts, Collection $distContacts): void
    {
        $this->sync = $sync;

        $uuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(function ($contact) use ($uuids): bool {
            return $uuids->contains($this->sync->backend->getUuid($contact['href']));
        });

        $this->updateContacts($this->sync, $missed);
    }

    /**
     * Get contacts data with addressbook-multiget request.
     *
     * @param  Collection  $refresh
     * @return PromiseInterface
     */
    private function refreshMultigetContacts(Collection $refresh): PromiseInterface
    {
        $addressDataAttributes = Arr::get($this->sync->subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        $hrefs = $refresh->pluck('href');

        return $this->sync->client->addressbookMultigetAsync('', [
            '{DAV:}getetag',
            [
                'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
                'value' => null,
                'attributes' => $addressDataAttributes,
            ],
        ], $hrefs)->then(function ($datas) {
            return collect($datas)
                ->filter(function ($contact): bool {
                    return isset($contact[200]);
                })
                ->map(function ($contact, $href): array {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                        'vcard' => $contact[200]['{'.CardDAVPlugin::NS_CARDDAV.'}address-data'],
                    ];
                });
        })->then(function ($contacts) {
            foreach ($contacts as $contact) {
                $this->syncLocalContact($contact['href'], $contact['etag'], $contact['vcard']);
            }
        });
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection  $contacts
     * @return PromiseInterface
     */
    private function refreshSimpleGetContacts(Collection $contacts): PromiseInterface
    {
        $requests = $contacts->map(function ($contact) {
            return new Request('GET', $contact['href']);
        })->toArray();

        return $this->sync->client->requestPool($requests, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($contacts) {
                if ($response->getStatusCode() === 200) {
                    Log::info(__CLASS__.' refreshSimpleGetContacts: GET '.$contacts[$index]['href']);

                    $this->syncLocalContact($contacts[$index]['href'],
                        $contacts[$index]['etag'],
                        $response->getBody()->detach()
                    );
                }
            },
        ]);
    }

    /**
     * Save contact to local storage.
     * @param  string  $href
     * @param  string  $etag
     * @param  string|resource|null $vcard
     */
    private function syncLocalContact(string $href, string $etag, $vcard): void
    {
        if ($vcard !== null) {
            $newtag = $this->sync->backend->updateCard($this->sync->subscription->addressbook->name, $href, $vcard);

            if ($newtag !== $etag) {
                Log::warning(__CLASS__.' syncLocalContact: wrong etag. Expected '.$etag.', get '.$newtag);
            }
        }
    }
}
