<?php

namespace App\Models\CardDAV\Backends;

use Sabre\DAV;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ExportVCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Sabre\CardDAV\Backend\AbstractBackend;

class MonicaCardDAVBackend extends AbstractBackend
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
        return [
            [
                'id'                => '0',
                'uri'               => 'contacts',
                'principaluri'      => MonicaPrincipalBackend::getPrincipalUser(),
                '{DAV:}displayname' => Auth::user()->name,
            ],
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
            'lastmodified' => $contact->updated_at,
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
        $contacts = Auth::user()->account
                        ->contacts()
                        ->real()
                        ->active()
                        ->get();

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
