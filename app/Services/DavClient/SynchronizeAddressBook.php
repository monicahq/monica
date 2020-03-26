<?php

namespace App\Services\DavClient;

use App\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BaseService;
use Illuminate\Support\Collection;
use Sabre\VObject\Component\VCard;
use Illuminate\Support\Facades\Log;
use App\Services\DavClient\Dav\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Models\Account\AddressBook;

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
            'addressbook_id' => 'required|integer|exists:addressbooks,id',
            /*
            'addressbook' => 'required|string|url',
            'addressBookId' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'capabilities' => 'required|array',
            'localSyncToken' => 'nullable|integer|exists:syncToken,id',
            'syncToken' => 'nullable|string',
            */
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

        $addressbook = AddressBook::where('account_id', $data['account_id'])
            ->findOrFail($data['addressbook_id']);

        $backend = new CardDAVBackend($user->account, $user);

        try {
            $client = $this->getClient($addressbook, $httpClient);

            $localChanges = $backend->getChangesForAddressBook($addressbook->addressBookId, $addressbook->localSyncToken, 1);

            $changes = $this->distantChanges($addressbook, $client, $backend);

            $this->getContacts($addressbook, $client, $backend, $changes);

            $this->pushContacts($addressbook, $client, $backend, $changes, $localChanges);

        } catch (ClientException $e) {
            $r = $e->getResponse();
            $s = (string) $r->getBody();
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }

    private function distantChanges($addressbook, Client $client, CardDAVBackend $backend)
    {
        $distantChanges = $this->getContactsList($addressbook, $client) ?? [];

        $refresh = collect();
        foreach ($distantChanges as $href => $contact) {
            if (isset($contact[200]) && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard')) {

                $localContact = $backend->getCard($addressbook->addressBookId, $href);
                $etag = $contact[200]['{DAV:}getetag'];

                if ($localContact !== false && $localContact['etag'] == $etag) {
                    // contact already exist, and the etag is the same
                    continue;
                }

                $refresh->push([
                    'href' => $href,
                    'etag' => $contact[200]['{DAV:}getetag'],
                ]);
            }
        }

        return $refresh;
    }

    private function getContacts($addressbook, Client $client, CardDAVBackend $backend, Collection $refresh)
    {
        if (Arr::get($addressbook->capabilities, 'addressbookMultiget', false)) {
            $refreshContacts = $this->refreshMultigetContacts($addressbook, $client, $refresh);
        } else {
            $refreshContacts = $this->refreshSimpleGetContacts($client, $refresh);
        }

        foreach ($refreshContacts as $contact)
        {
            $newtag = $backend->updateCard($addressbook->addressBookId, $contact['href'], $contact['vcard']);

            if ($newtag != $contact['etag']) {
                Log::warning(__CLASS__.' refreshContacts: wrong etag update');
            }
        }
    }

    private function refreshMultigetContacts($addressbook, Client $client, Collection $refresh)
    {
        $addressDataAttributes = Arr::get($addressbook->capabilities, 'addressData', [
            'content-type' => 'text/vcard',
            'version' => '4.0',
        ]);

        $hrefs = $refresh->pluck('href');
        $datas = $client->addressbookMultiget('', [
            '{DAV:}getetag',
            [
                'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
                'value' => null,
                'attributes' => $addressDataAttributes,
            ]
        ], $hrefs);

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
    }

    private function refreshSimpleGetContacts(Client $client, Collection $refresh)
    {
        $refreshContacts = collect();

        foreach ($refresh as $contact) {
            $c = $client->request('GET', $contact['href']);
            if ($c['statusCode'] === 200) {
                $refreshContacts->push([
                    'href' => $contact['href'],
                    'etag' => $contact['etag'],
                    'vcard' => $c['body'],
                ]);
            }
        }

        return $refreshContacts;
    }

    private function pushContacts($addressbook, Client $client, CardDAVBackend $backend, Collection $refresh, array $localChanges)
    {
        $refreshIds = $refresh->pluck('href');

        // All added contact must be pushed
        $localChangesAdd = collect($localChanges['added']);

        // We don't push contact that have just been updated
        $localChangesUpdate = collect($localChanges['modified'])->reject(function ($uri) use ($refreshIds, $backend) {
            $uuid = $backend->getUuid($uri);
            return $refreshIds->contains($uuid);
        });

        foreach ($localChangesUpdate as $uri)
        {
            $contact = $backend->getCard($addressbook->addressBookId, $uri);
            $client->request('PUT', $uri, $contact['carddata'], [
                'If-Match' => '*'
            ]);
        }
        foreach ($localChangesAdd as $uri)
        {
            $contact = $backend->getCard($addressbook->addressBookId, $uri);
            $client->request('PUT', $uri, $contact['carddata'], []);
        }
    }

    private function getContactsList($addressbook, Client $client): array
    {
        // With sync-collection
        if (Arr::get($addressbook->capabilities, 'syncCollection', false)) {

            $syncToken = $addressbook->syncToken ?? '';

            // get the current distant syncToken
            $distantSyncToken = $client->getProperty('{DAV:}sync-token');

            if ($syncToken == $distantSyncToken) {
                // no change at all
                return [];
            }

            // get sync
            $collection = $client->syncCollection('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], $syncToken);

            // save the new syncToken as current one
            $addressbook->syncToken = $collection['synctoken'];
            $addressbook->save();

        } else {

            // synchronisation
            $collection = $client->propFind('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }

        return $collection;
    }



    private function getClient($addressbook, GuzzleClient $client = null) : Client
    {
        return new Client([
            'base_uri' => $addressbook->uri,
            'username' => $addressbook->username,
            'password' => $addressbook->password,
        ], $client);
    }
}
