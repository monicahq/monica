<?php

namespace App\Models\CardDAV\Backends;

use Sabre\DAV;
use Sabre\VObject\Reader;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MonicaCardDAVBackend implements \Sabre\CardDAV\Backend\BackendInterface
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
        Log::debug(__CLASS__.' getAddressBooksForUser', func_get_args());

        return [
            [
                'id' => '0',
                'uri' => 'Contacts',
                'principaluri' => 'principals/'.Auth::user()->email,
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
        Log::debug(__CLASS__.' updateAddressBook', func_get_args());

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
        Log::debug(__CLASS__.' createAddressBook', func_get_args());

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
        Log::debug(__CLASS__.' deleteAddressBook', func_get_args());

        return false;
    }

    private function prepareCard($contact)
    {
        // The standard for most of these fields can be found on https://tools.ietf.org/html/rfc6350

        // Basic information
        $vcard = new VCard([
            'FN'  => $this->escape($contact->first_name.' '.$contact->last_name),
            'N'   => [
                $this->escape($contact->last_name),
                $this->escape($contact->first_name),
                $this->escape($contact->middle_name)
            ],
            'UID' => $contact->hashid(),
        ]);

        // Nickname
        if (!empty($contact->nickname)) {
            $vcard->add('NICKNAME', $this->escape($contact->nickname));
        }

        // Picture
        $picture = $contact->getAvatarURL();
        if (! is_null($picture)) {
            $vcard->add('PHOTO', $picture);
        }

        // Gender
        switch ($contact->gender->name) {
            case 'Man':
                $gender = 'M';
                break;
            case 'Woman':
                $gender = 'F';
                break;
            default:
                $gender = 'O';
                break;
        }
        $vcard->add('GENDER', $gender.';'.$contact->gender->name);

        // Birthday
        if (! is_null($contact->birthdate)) {
            $date = $contact->birthdate->date->format('Ymd');
            $vcard->add('BDAY', $date);
        }

        // Contactfields
        foreach ($contact->contactFields as $contactField) {
            switch ($contactField->contactFieldType->type) {
                case 'phone':
                    $vcard->add('TEL', $this->escape($contactField->data));
                    break;
                case 'email':
                    $vcard->add('EMAIL', $this->escape($contactField->data));
                    break;
                default:
                    break;
            }
            switch ($contactField->contactFieldType->name) {
                case 'Facebook':
                    $vcard->add('socialProfile', $this->escape($contactField->data), ['type' => 'facebook']);
                    break;
                // ... Twitter, Whatsapp, Telegram, other
                default:
                    break;
            }
        }

        return [
            'id' => $contact->hashid(),
            'etag' => md5($vcard->serialize()),
            'uri' => $contact->id,
            'lastmodified' => $contact->updated_at->timestamp,
            'carddata' => $vcard->serialize(),
        ];
    }

    private function escape($value) : string
    {
        return ! empty((string) $value) ? trim((string) $value) : (string) null;
    }

    private function prepareCards($contacts)
    {
        $results = [];

        foreach ($contacts as $contact) {
            $results[] = $this->prepareCard($contact);
        }

        Log::debug(__CLASS__.' prepareCards', ['count' => count($results)]);

        return $results;
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
        Log::debug(__CLASS__.' getCards', func_get_args());

        $contacts = Auth::user()->account
                        ->contacts()
                        ->real()
                        ->active()
                        ->get();

        return $this->prepareCards($contacts);
    }

    /**
     * Returns a specfic card.
     *
     * The same set of prope
     * @param mixed $addressBookId
     * @param string $cardUri
     * @return array
     */
    public function getCard($addressBookId, $cardUri)
    {
        Log::debug(__CLASS__.' getCard', func_get_args());

        $contact = Contact::where('account_id', Auth::user()->account_id)
            ->findOrFail($cardUri);

        return $this->prepareCard($contact);
    }

    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId
     * @param array $uris
     * @return array
     */
    public function getMultipleCards($addressBookId, array $uris)
    {
        Log::debug(__CLASS__.' getMultipleCards', func_get_args());

        $contacts = Contact::where('account_id', Auth::user()->account_id)
            ->whereIn('id', $uris)
            ->get();

        return $this->prepareCards($contacts);
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
        Log::debug(__CLASS__.' createCard', func_get_args());

        return $this->importCard($cardUri, $cardData);
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
        Log::debug(__CLASS__.' updateCard', func_get_args());

        return $this->importCard($cardUri, $cardData);
    }

    private function importCard($cardUri, $cardData)
    {
        $contact_id = $cardUri;
        if ($cardUri) {
            try {
                $contact_id = app('idhasher')->decodeId($cardUri);
            } catch (\App\Exceptions\WrongIdException $e) {
                $contact_id = $cardUri;
            }
        }

        try {
            $result = (new ImportVCard(Auth::user()->account_id))
                ->execute([
                    'contact_id' => $contact_id,
                    'user_id' => Auth::user()->id,
                    'entry' => $cardData,
                    'behaviour' => ImportVCard::BEHAVIOUR_REPLACE,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' importCard', (string) $e);
        }

        if ($result > 0) {
            $contact = Contact::where('account_id', Auth::user()->account_id)
                ->find($result);
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
        Log::debug(__CLASS__.' deleteCard', func_get_args());
    }
}
