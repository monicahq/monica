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
            'addressbook' => 'required|string|url',
            'addressbookId' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
            'localSyncToken' => 'nullable|integer|exists:syncToken,id',
            'syncToken' => 'nullable|string',
        ];
    }

    /**
     *
     * @param array $data
     * @return VCard
     */
    public function execute(array $data, GuzzleClient $httpClient = null)
    {
        $data = [
            'account_id' => 1,
            'user_id' => 1,
            'addressbook' => 'http://monica.test/dav/addressbooks/admin@admin.com/contacts/',
            'addressbookId' => 0,
            'username' => 'admin@admin.com',
            'password' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZjUzYmRkYzJhNzQ0ZTgxZDRiYmUwMTQ2ODY1YTA3ZTIyMWM0ZTQ5YzkyNzJkN2FhZmE1ODk5ZDRmM2NkZGRlMWQyMWQ5MmM5M2E4YTNkMGMiLCJpYXQiOjE1ODQ4MDkxNDQsIm5iZiI6MTU4NDgwOTE0NCwiZXhwIjoxNjE2MzQ1MTQ0LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.MUtcgy3PWk9McA59zx4SBJxKAkdSiGv1a9ZtVKwlgtk09bJEx1lJgymGSfDlrNqKvD2LqhTu0y95jLZNoj4-uM6DBZm3RMo18mw2xCEywB4st1hZpMYSYoOmtrOcsZweoP5r31zf_jzMX3mLde6MAeEkJcotGfO9z57M74FquLKixZRLvVruES2DcZoL1hwCKoxvv11BGRE78RQsWiipv0cfgmcSNEQVR820BWkM0X_4WwpufJdzZ5p1EpTy5AP2XXlx6amGXqxgMUIY7C-KyF1uw1Rmr6B-bTcMLJHZBH6TzU0yFoaJnhZZ9tJFyf7E70BL8SaO9_P6nA7ACjDREjAJBD9dZYrP46G-mqJXjWyVOcDVJZNW7dhF5vnEp7gghIVWhAm4lLy5nPI_CNpB0mqPrdkj57Avoi3MAEwf4ADy9CZp1EoLZIvNjBuMpgwwONTF5oP18NMaHJcsbFkmviY7eW-DIuIcNtCuoAM7Q4ulhuVX4tVry5NLsiab0_W8_l63C_n1-ICpv2t04jSh9H3SwgIXAZXhe-0vMt0gTIc3c_1HZ4eRd1kOuUs-708Esiq7J_Nt98PJZB8AP6qbeuScI0Cxnm7IulJ1WaI7mLjA7JPDvISeL2rrYjwqmguDbA8nQ7UjEq1dLN-PaAaL08p_iKU3ssPV2YziNSi_Alc',
            'localSyncToken' => '22',
            'syncToken' => 'http://sabre.io/ns/sync/15',
        ];

        /* TESTS */

        $this->validate($data);

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        $backend = new CardDAVBackend($user->account, $user);

        try {
            $client = $this->getClient($data, $httpClient);

            $localChanges = $backend->getChangesForAddressBook($data['addressbookId'], $data['localSyncToken'], 1);

            $supportedReportSet = $client->getSupportedReportSet();

            $refresh = $this->distantChanges($data, $client, $supportedReportSet, $backend);

            $this->refreshContacts($data, $client, $supportedReportSet, $backend, $refresh);

            $this->pushContacts($data, $client, $supportedReportSet, $backend, $refresh, $localChanges);

        } catch (ClientException $e) {
            $r = $e->getResponse();
            $s = (string) $r->getBody();
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }

    private function distantChanges(array $data, Client $client, array $supportedReportSet, CardDAVBackend $backend)
    {
        $distantChanges = $this->getContactsList($data, $client, $supportedReportSet) ?? [];

        $refresh = collect();
        foreach ($distantChanges as $href => $contact) {
            if (isset($contact[200]) && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard')) {

                $localContact = $backend->getCard($data['addressbookId'], $href);
                $etag = $contact[200]['{DAV:}getetag'];

                if ($localContact !== false && $localContact['etag'] == $etag) {
                    // contact already exist, and the etag is the same
                    //continue;
                }

                $refresh->push([
                    'href' => $href,
                    'etag' => $contact[200]['{DAV:}getetag'],
                ]);
            }
        }

        return $refresh;
    }

    private function refreshContacts(array $data, Client $client, array $supportedReportSet, CardDAVBackend $backend, Collection $refresh)
    {
        $refreshContacts = collect();
        if (in_array('{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-multiget', $supportedReportSet)) {

            // get the supported card format
            $addressData = collect($client->getProperty('{'.CardDAVPlugin::NS_CARDDAV.'}supported-address-data'));
            $datas = $addressData->firstWhere('attributes.version', '4.0');
            if (!$datas) {
                $datas = $addressData->firstWhere('attributes.version', '3.0');
            }

            if (!$datas) {
                // It should not happen !
                $datas = [
                    'attributes' => [
                        'content-type' => 'text/vcard',
                        'version' => '4.0',
                    ],
                ];
            }

            $hrefs = $refresh->pluck('href');
            $datas = $client->addressbookMultiget('', [
                '{DAV:}getetag',
                [
                    'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
                    'value' => null,
                    'attributes' => [
                        'content-type' => $datas['attributes']['content-type'],
                        'version' => $datas['attributes']['version'],
                    ],
                ]
            ], $hrefs);

            foreach ($datas as $href => $contact) {
                if (isset($contact[200])) {
                    $refreshContacts->push([
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                        'vcard' => $contact[200]['{'.CardDAVPlugin::NS_CARDDAV.'}address-data'],
                    ]);
                }
            }

        } else {

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
        }

        foreach ($refreshContacts as $contact)
        {
            $newtag = $backend->updateCard($data['addressbookId'], $contact['href'], $contact['vcard']);

            if ($newtag != $contact['etag']) {
                Log::warning(__CLASS__.' refreshContacts: wrong etag update');
            }
        }
    }

    private function pushContacts(array $data, Client $client, array $supportedReportSet, CardDAVBackend $backend, Collection $refresh, array $localChanges)
    {
        $refreshIds = $refresh->pluck('href');

        // All added contact must be pushed
        $localChangesAdd = collect($localChanges['added']);

        // We don't push contact that have just been updated
        $localChangesUpdate = collect($localChanges['modified'])->reject(function ($uri) use ($refreshIds, $backend) {
            $uuid = $backend->getUuid($uri);
            return $refreshIds->contains($uuid);
        });

        foreach ($localChangesAdd->union($localChangesUpdate) as $uri)
        {
            $contact = $backend->getCard($data['addressbookId'], $uri);
            $client->request('PUT', $uri, $contact['carddata'], [
                'If-Match' => '*'
            ]);
        }
    }

    private function getContactsList(array $data, Client $client, $supportedReportSet): array
    {
        // With sync-collection
        if (in_array('{DAV:}sync-collection', $supportedReportSet)) {

            $syncToken = Arr::get($data, 'syncToken', '');

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
            $newSyncToken = $collection['synctoken'];

            // TODO

        } else {

            // synchronisation
            $collection = $client->propFind('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }

        return $collection;
    }



    private function getClient(array $data, GuzzleClient $client = null) : Client
    {
        $settings = [
            'base_uri' => $data['addressbook'],
            'username' => $data['username'],
            'password' => $data['password'],
        ];

        return new Client($settings, $client);
    }
}
