<?php

namespace App\Http\Controllers\DAV\Backend\CardDAV;

use Sabre\CardDAV\AddressBook as BaseAddressBook;

class AddressBook extends BaseAddressBook
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
            [
                'privilege' => '{DAV:}write-content',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}bind',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];
    }

    /**
     * This method returns the ACL's for card nodes in this address book.
     * The result of this method automatically gets passed to the
     * card nodes in this address book.
     *
     * @return array
     */
    public function getChildACL()
    {
        return $this->getACL();
    }

    /**
     * Returns the last modification date.
     *
     * @return int|null
     */
    public function getLastModified()
    {
        if ($this->carddavBackend instanceof CardDAVBackend) {
            $date = $this->carddavBackend->getLastModified();
            if (! is_null($date)) {
                return $date->timestamp;
            }
        }
    }
}
