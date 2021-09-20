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
use App\Services\DavClient\Utils\Model\ContactUpdateDto;

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
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $refresh
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
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $refresh
     * @return PromiseInterface
     */
    private function refreshMultigetContacts(Collection $refresh): PromiseInterface
    {
        $hrefs = $refresh->pluck('uri');

        return $this->sync->client->addressbookMultigetAsync('', [
            '{DAV:}getetag',
            $this->getAddressDataProperty(),
        ], $hrefs)
        ->then(function ($datas) {
            return collect($datas)
                ->filter(function (array $contact): bool {
                    return isset($contact[200]);
                })
                ->map(function (array $contact, $href): ContactUpdateDto {
                    return new ContactUpdateDto(
                        $href,
                        Arr::get($contact, '200.{DAV:}getetag'),
                        Arr::get($contact, '200.{'.CardDAVPlugin::NS_CARDDAV.'}address-data')
                    );
                });
        })->then(function (Collection $contacts) {
            $contacts->each(function (ContactUpdateDto $contact) {
                $this->syncLocalContact($contact);
            });
        });
    }

    /**
     * Get data for address-data property.
     *
     * @return array
     */
    private function getAddressDataProperty(): array
    {
        $addressDataAttributes = Arr::get($this->sync->subscription->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        return [
            'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
            'value' => null,
            'attributes' => $addressDataAttributes,
        ];
    }

    /**
     * Get contacts data with request.
     *
     * @param  Collection<array-key, \App\Services\DavClient\Utils\Model\ContactDto>  $requests
     * @return PromiseInterface
     */
    private function refreshSimpleGetContacts(Collection $requests): PromiseInterface
    {
        $inputs = $requests->map(function ($contact) {
            return new Request('GET', $contact->uri);
        })->toArray();

        return $this->sync->client->requestPool($inputs, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($requests) {
                if ($response->getStatusCode() === 200) {
                    /** @var \App\Services\DavClient\Utils\Model\ContactDto $request */
                    $request = $requests[$index];

                    $this->syncLocalContact(new ContactUpdateDto(
                        $request->uri,
                        $request->etag,
                        $response->getBody()->detach(),
                    ));
                }
            },
        ]);
    }

    /**
     * Save contact to local storage.
     *
     * @param  ContactUpdateDto  $contact
     */
    private function syncLocalContact(ContactUpdateDto $contact): void
    {
        if ($contact->card !== null) {
            Log::info(__CLASS__.' syncLocalContact: update '.$contact->uri);

            $newtag = $this->sync->backend->updateCard($this->sync->addressBookName(), $contact->uri, $contact->card);

            if ($newtag !== $contact->etag) {
                Log::warning(__CLASS__.' syncLocalContact: wrong etag when updating contact. Expected '.$contact->etag.', get '.$newtag);
            }
        }
    }
}
