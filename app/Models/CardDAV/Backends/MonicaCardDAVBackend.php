<?php

namespace App\Models\CardDAV\Backends;

use Sabre\DAV;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ExportVCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Plugin as CardDAVPlugin;

class MonicaCardDAVBackend extends AbstractBackend implements SyncSupport
{
    /**
     * Returns the list of addressbooks for a specific user.
     *
     * Every addressbook should have the following properties:
     *   id - an arbitrary unique id
     *   uri - the 'basename' part of the url
     *   principaluri - Same as the passed parameter
     *
     * Any additional clark-notation property may be passed besides this. Some
     * common ones are :
     *   {DAV:}displayname
     *   {urn:ietf:params:xml:ns:carddav}addressbook-description
     *   {http://calendarserver.org/ns/}getctag
     *
     * @param string $principalUri
     * @return array
     */
    public function getAddressBooksForUser($principalUri)
    {
        $name = Auth::user()->name;
        $token = $this->getSyncToken();

        return [
            [
                'id'                                 => '0',
                'uri'                                => 'contacts',
                'principaluri'                       => MonicaPrincipalBackend::getPrincipalUser(),
                '{DAV:}displayname'                  => $name,
                '{http://sabredav.org/ns}sync-token' => $token->id,
                '{DAV:}sync-token'                   => $token->id,
                '{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-description' => $name,
            ],
        ];
    }

    /**
     * This method returns a sync-token for this collection.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return SyncToken
     */
    public function getSyncToken()
    {
        $tokens = SyncToken::where([
            ['account_id', Auth::user()->account_id],
            ['user_id', Auth::user()->id],
        ])
            ->orderBy('created_at')
            ->get();

        if ($tokens->count() <= 0) {
            $token = $this->createSyncToken();
        } else {
            $token = $tokens->last();

            if ($token->timestamp < $this->getLastModified()) {
                $token = $this->createSyncToken();
            }
        }

        return $token;
    }

    /**
     * Create a token.
     *
     * @return SyncToken
     */
    private function createSyncToken()
    {
        $max = $this->getLastModified();

        return SyncToken::create([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::user()->id,
            'timestamp' => $max,
        ]);
    }

    /**
     * Returns the last modification date.
     *
     * @return \Carbon\Carbon
     */
    public function getLastModified()
    {
        return $this->getContacts()
                    ->max('updated_at');
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified address book.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property. This is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $addressBookId
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null)
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = SyncToken::where([
                'account_id' => Auth::user()->account_id,
                'user_id' => Auth::user()->id,
            ])->find($syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return;
            }

            $timestamp = $token->timestamp;
            $token = $this->getSyncToken();
        } else {
            $token = $this->createSyncToken();
            $timestamp = null;
        }

        $contacts = $this->getContacts();

        $modified = $contacts->filter(function ($contact) use ($timestamp) {
            return ! is_null($timestamp) &&
                   $contact->updated_at > $timestamp &&
                   $contact->created_at < $timestamp;
        });
        $added = $contacts->filter(function ($contact) use ($timestamp) {
            return is_null($timestamp) ||
                   $contact->created_at >= $timestamp;
        });

        return [
            'syncToken' => $token->id,
            'added' => $added->map(function ($contact) {
                return $this->encodeUri($contact);
            })->toArray(),
            'modified' => $modified->map(function ($contact) {
                return $this->encodeUri($contact);
            })->toArray(),
            'deleted' => [],
        ];
    }

    /**
     * Updates properties for an address book.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void|bool
     */
    public function updateAddressBook($addressBookId, DAV\PropPatch $propPatch)
    {
        return false;
    }

    /**
     * Creates a new address book.
     *
     * This method should return the id of the new address book. The id can be
     * in any format, including ints, strings, arrays or objects.
     *
     * @param string $principalUri
     * @param string $url Just the 'basename' of the url.
     * @param array $properties
     * @return int|bool
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
        return false;
    }

    /**
     * Deletes an entire addressbook and all its contents.
     *
     * @param mixed $addressBookId
     * @return void|bool
     */
    public function deleteAddressBook($addressBookId)
    {
        return false;
    }

    private function prepareCard($contact)
    {
        if (! $contact) {
            return;
        }

        try {
            $vcard = (new ExportVCard())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'contact_id' => $contact->id,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' prepareCard: '.(string) $e);
        }

        $carddata = $vcard->serialize();

        return [
            'id' => $contact->hashID(),
            'uri' => $this->encodeUri($contact),
            'carddata' => $carddata,
            'etag' => '"'.md5($carddata).'"',
            'lastmodified' => $contact->updated_at->timestamp,
        ];
    }

    private function encodeUri($contact)
    {
        return urlencode($contact->uuid.'.vcf');
    }

    private function decodeUri($uri)
    {
        return pathinfo(urldecode($uri), PATHINFO_FILENAME);
    }

    /**
     * Returns the contact for the specific uri.
     *
     * @param string  $uri
     * @return Contact
     */
    private function getContact($uri)
    {
        try {
            return Contact::where([
                'account_id' => Auth::user()->account_id,
                'uuid' => $this->decodeUri($uri),
            ])->first();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the collection of all active contacts.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getContacts()
    {
        return Auth::user()->account
                    ->contacts()
                    ->real()
                    ->active()
                    ->get();
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressbookId
     * @return array
     */
    public function getCards($addressbookId)
    {
        $contacts = $this->getContacts();

        return $contacts->map(function ($contact) {
            return $this->prepareCard($contact);
        });
    }

    /**
     * Returns a specific card.
     *
     * The same set of prope
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */
    public function getCard($addressBookId, $cardUri)
    {
        $contact = $this->getContact($cardUri);

        return $this->prepareCard($contact);
    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData)
    {
        return $this->importCard(null, $cardData);
    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @param string $cardData
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        return $this->importCard($cardUri, $cardData);
    }

    private function importCard($cardUri, $cardData)
    {
        $contact_id = null;
        if ($cardUri) {
            $contact = $this->getContact($cardUri);

            if ($contact) {
                $contact_id = $contact->id;
            }
        }

        try {
            $result = (new ImportVCard(Auth::user()->account_id))
                ->execute([
                    'contact_id' => $contact_id,
                    'entry' => $cardData,
                    'behaviour' => ImportVCard::BEHAVIOUR_REPLACE,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' importCard: '.(string) $e);
        }

        if (! array_has($result, 'error')) {
            $contact = Contact::where('account_id', Auth::user()->account_id)
                ->find($result['contact_id']);
            $card = $this->prepareCard($contact);

            return $card['etag'];
        }
    }

    /**
     * Deletes a card.
     *
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri)
    {
        return false;
    }
}
