<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;

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
     * @param  SyncDto  $sync
     * @param  Collection  $refresh
     * @return PromiseInterface
     */
    public function execute(SyncDto $sync, Collection $refresh): PromiseInterface
    {
        $this->sync = $sync;

        return $this->hasCapability('addressbookMultiget')
            ? $this->refreshMultigetContacts($refresh)
            : $this->refreshSimpleGetContacts($refresh);
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
        ], $hrefs)
        ->then(function ($datas) {
            return collect($datas)
                ->filter(function ($contact): bool {
                    return isset($contact[200]);
                })
                ->map(function ($contact, $href): array {
                    return [
                        'href' => $href,
                        'etag' => Arr::get($contact, '200.{DAV:}getetag'),
                        'vcard' => Arr::get($contact, '200.{'.CardDAVPlugin::NS_CARDDAV.'}address-data'),
                    ];
                });
        })->then(function ($contacts) {
            foreach ($contacts as $contact) {
                Log::info(__CLASS__.' refreshMultigetContacts: GET '.$contact['href']);

                $this->syncLocalContact($contact['href'], $contact['etag'], $contact['vcard']);
            }
        });
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection  $requests
     * @return PromiseInterface
     */
    private function refreshSimpleGetContacts(Collection $requests): PromiseInterface
    {
        $inputs = $requests->map(function ($contact) {
            return new Request('GET', $contact['href']);
        })->toArray();

        return $this->sync->client->requestPool($inputs, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($requests) {
                if ($response->getStatusCode() === 200) {
                    Log::info(__CLASS__.' refreshSimpleGetContacts: GET '.$requests[$index]['href']);

                    $this->syncLocalContact($requests[$index]['href'],
                        $requests[$index]['etag'],
                        $response->getBody()->detach()
                    );
                }
            },
        ]);
    }

    /**
     * Save contact to local storage.
     *
     * @param  string  $href
     * @param  string  $etag
     * @param  string|resource|null  $vcard
     */
    private function syncLocalContact(string $href, string $etag, $vcard): void
    {
        if ($vcard !== null) {
            $newtag = $this->sync->backend->updateCard($this->sync->subscription->addressbook->name, $href, $vcard);

            if ($newtag !== $etag) {
                Log::warning(__CLASS__.' syncLocalContact: wrong etag when updating contact. Expected '.$etag.', get '.$newtag);
            }
        }
    }
}
