<?php

namespace App\Models\CardDAV;

use Sabre\CardDAV\AddressBookHome;

class MonicaAddressBookHome extends AddressBookHome
{
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL()
    {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];
    }

    /**
     * Returns a list of addressbooks.
     *
     * @return array
     */
    public function getChildren()
    {
        $addressbooks = $this->carddavBackend->getAddressBooksForUser($this->principalUri);

        return collect($addressbooks)->map(function ($addressbook) {
            return new MonicaAddressBook($this->carddavBackend, $addressbook);
        });
    }
}
