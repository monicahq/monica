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

class AddressBookContactsUpdater
{
    use HasCapability;

    /**
     * @var AddressBookSynchronizer
     */
    private $synchronizer;

    public function __construct(AddressBookSynchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    protected function subscription(): AddressBookSubscription
    {
        return $this->synchronizer->subscription;
    }

    protected function backend(): CardDAVBackend
    {
        return $this->synchronizer->backend;
    }

    /**
     * Update local contacts.
     *
     * @param  Collection  $refresh
     */
    public function updateContacts(Collection $refresh)
    {
        $refreshContacts = $this->hasCapability('addressbookMultiget')
            ? $this->refreshMultigetContacts($refresh)
            : $this->refreshSimpleGetContacts($refresh);

        return $refreshContacts->then(function ($contacts) {
            foreach ($contacts as $contact) {
                $newtag = $this->backend()->updateCard($this->subscription()->addressbook->name, $contact['href'], $contact['vcard']);

                if ($newtag != $contact['etag']) {
                    Log::warning(__CLASS__.' getContacts: wrong etag. Expected '.$contact['etag'].', get '.$newtag);
                }
            }
        });
    }

    /**
     * Update local missed contacts.
     *
     * @param  Collection  $localContacts
     * @param  Collection  $distContacts
     */
    public function updateMissedContacts(Collection $localContacts, Collection $distContacts)
    {
        $uuids = $localContacts->pluck('uuid');

        $missed = $distContacts->reject(function ($contact) use ($uuids): bool {
            return $uuids->contains($this->backend()->getUuid($contact['href']));
        });

        $this->updateContacts($missed);
    }

    /**
     * Get contacts data with addressbook-multiget request.
     *
     * @param  Collection  $refresh
     * @return PromiseInterface
     */
    private function refreshMultigetContacts(Collection $refresh): PromiseInterface
    {
        $addressDataAttributes = Arr::get($this->subscription()->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        $hrefs = $refresh->pluck('href');

        return $this->synchronizer->client->addressbookMultigetAsync('', [
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
        $requests = $contacts->map(function ($uri) {
            return new Request('GET', $uri);
        })->toArray();

        return $this->synchronizer->client->requestPool($requests, [
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
            },
        ]);
    }
}
