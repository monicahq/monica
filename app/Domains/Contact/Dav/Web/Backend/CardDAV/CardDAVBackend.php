<?php

namespace App\Domains\Contact\Dav\Web\Backend\CardDAV;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Domains\Contact\Dav\Services\ExportVCard;
use App\Domains\Contact\Dav\Services\GetEtag;
use App\Domains\Contact\Dav\Web\Backend\IDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\SyncDAVBackend;
use App\Domains\Contact\Dav\Web\DAVACL\PrincipalBackend;
use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use ReturnTypeWillChange;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Sabre\DAV;
use Sabre\DAV\Server as SabreServer;
use Sabre\DAV\Sync\Plugin as DAVSyncPlugin;

/**
 * @template TValue of ?Contact
 *
 * @implements IDAVBackend<TValue>
 */
class CardDAVBackend extends AbstractBackend implements IDAVBackend, SyncSupport
{
    use SyncDAVBackend;

    public function __construct(
        private User $user
    ) {
    }

    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri(): string
    {
        return 'contacts';
    }

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
     * @param  string  $principalUri
     * @return array
     */
    public function getAddressBooksForUser($principalUri): array
    {
        return $this->user->vaults()
            ->wherePivot('permission', '<=', Vault::PERMISSION_VIEW)
            ->get()
            ->map(fn (Vault $vault) => $this->getAddressBookDetails($vault))
            ->toArray();
    }

    private function getAddressBookDetails(Vault $vault): array
    {
        $token = $this->getCurrentSyncToken($vault->uuid);

        $des = [
            'id' => $vault->uuid,
            'uri' => $vault->name,
            'principaluri' => PrincipalBackend::getPrincipalUser($this->user),
            '{DAV:}displayname' => __('Contacts'),
            '{'.CardDAVPlugin::NS_CARDDAV.'}addressbook-description' => $vault->name,
        ];
        if ($token) {
            $des += [
                '{DAV:}sync-token' => $token->id,
                '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token->id,
                '{'.CalDAVPlugin::NS_CALENDARSERVER.'}getctag' => DAVSyncPlugin::SYNCTOKEN_PREFIX.$token->id,
            ];
        }

        return $des;
    }

    /**
     * Extension for Calendar objects.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return '.vcf';
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
     * @param  string  $addressBookId
     * @param  string  $syncToken
     * @param  int  $syncLevel
     * @param  int|null  $limit
     * @return array|null
     */
    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null): ?array
    {
        return $this->getChanges($addressBookId, $syncToken);
    }

    /**
     * Prepare datas for this contact.
     *
     * @param  Contact  $contact
     * @return array
     */
    public function prepareCard(Contact $contact): array
    {
        $carddata = $contact->vcard;
        try {
            if (empty($carddata)) {
                $carddata = $this->refreshObject($contact);
            }

            $etag = app(GetEtag::class)->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $contact->vault_id,
                'contact_id' => $contact->id,
            ]);

            return [
                'contact_id' => $contact->id,
                'uri' => $this->encodeUri($contact),
                'carddata' => $carddata,
                'etag' => $etag,
                'distant_etag' => $contact->distant_etag,
                'lastmodified' => $contact->updated_at->timestamp,
            ];
        } catch (\Exception $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'carddata' => $carddata,
                'contact_id' => $contact->id,
                $e,
            ]);
            throw $e;
        }
    }

    /**
     * Get the new exported version of the object.
     *
     * @param  mixed  $obj  contact
     * @return string
     */
    protected function refreshObject($obj): string
    {
        $vcard = app(ExportVCard::class)
            ->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $obj->vault_id,
                'contact_id' => $obj->id,
            ]);

        return $vcard->serialize();
    }

    /**
     * Returns the contact for the specific uuid.
     *
     * @param  string|null  $collectionId
     * @param  string  $uuid
     * @return Contact|null
     */
    public function getObjectUuid(?string $collectionId, string $uuid): ?Contact
    {
        $vault = $this->user->vaults()
            ->where('uuid', $collectionId)
            ->wherePivot('permission', '<=', Vault::PERMISSION_VIEW)
            ->first();

        if (! $vault) {
            throw new NotEnoughPermissionException();
        }

        return Contact::where([
            'uuid' => $uuid,
            'vault_id' => $vault->id,
        ])->first();
    }

    /**
     * Returns the collection of all active contacts.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection<array-key, Contact>
     */
    public function getObjects(?string $collectionId): Collection
    {
        $vaults = $this->user->vaults()
            ->wherePivot('permission', '<=', Vault::PERMISSION_VIEW);

        if ($collectionId !== null) {
            $vaults = $vaults->where('uuid', $collectionId);
        }

        return $vaults->get()
            ->map(fn ($vault) => $vault->contacts()
                    ->active()
                    ->get()
            )
            ->flatten();
    }

    /**
     * Returns the collection of deleted contacts.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection<array-key, Contact>
     */
    public function getDeletedObjects(?string $collectionId): Collection
    {
        $vaults = $this->user->vaults()
            ->wherePivot('permission', '<=', Vault::PERMISSION_VIEW);

        if ($collectionId !== null) {
            $vaults = $vaults->where('uuid', $collectionId);
        }

        return $vaults->get()
            ->map(fn ($vault) => $vault->contacts()
                    ->onlyTrashed()
                    ->get()
            )
            ->flatten();
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
     * @param  mixed  $addressbookId
     * @return array
     */
    public function getCards($addressbookId): array
    {
        $contacts = $this->getObjects($addressbookId);

        return $contacts
            ->map(fn ($contact) => $this->prepareCard($contact))
            ->toArray();
    }

    /**
     * Returns a specific card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param  mixed  $addressBookId
     * @param  string  $cardUri
     * @return array|bool
     */
    #[ReturnTypeWillChange]
    public function getCard($addressBookId, $cardUri)
    {
        $contact = $this->getObject($addressBookId, $cardUri);

        return $contact === null
            ? false
            : $this->prepareCard($contact);
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
     * @param  mixed  $addressBookId
     * @param  string  $cardUri
     * @param  string  $cardData
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData): ?string
    {
        return $this->updateCard($addressBookId, $cardUri, $cardData);
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
     * @param  mixed  $addressBookId
     * @param  string  $cardUri
     * @param  string|resource  $cardData
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData): ?string
    {
        $vault = $this->user->vaults()
            ->wherePivot('permission', '<=', Vault::PERMISSION_EDIT)
            ->where('uuid', $addressBookId)
            ->firstOrFail();

        $job = new UpdateVCard([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $vault->id,
            'uri' => $cardUri,
            'card' => $cardData,
        ]);

        Bus::batch([$job])
            ->allowFailures()
            ->dispatch();

        return null;
    }

    /**
     * Deletes a card.
     *
     * @param  mixed  $addressBookId
     * @param  string  $cardUri
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri): bool
    {
        $contact = $this->getObject($addressBookId, $cardUri);

        if ($contact) {
            DestroyContact::dispatch([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $contact->vault_id,
                'contact_id' => $contact->id,
            ]);

            return true;
        }

        return false;
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
     * @param  string  $addressBookId
     * @param  \Sabre\DAV\PropPatch  $propPatch
     * @return bool|null
     */
    public function updateAddressBook($addressBookId, DAV\PropPatch $propPatch): ?bool
    {
        return null;
    }

    /**
     * Creates a new address book.
     *
     * This method should return the id of the new address book. The id can be
     * in any format, including ints, strings, arrays or objects.
     *
     * @param  string  $principalUri
     * @param  string  $url  Just the 'basename' of the url.
     * @param  array  $properties
     * @return int|bool
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
        return false;
    }

    /**
     * Deletes an entire addressbook and all its contents.
     *
     * @param  mixed  $addressBookId
     * @return bool|null
     */
    public function deleteAddressBook($addressBookId): ?bool
    {
        return false;
    }
}
