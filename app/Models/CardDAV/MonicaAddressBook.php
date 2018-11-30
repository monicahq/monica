<?php

namespace App\Models\CardDAV;

use Sabre\CardDAV\AddressBook;
use Illuminate\Support\Facades\Auth;

class MonicaAddressBook extends AddressBook
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
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
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
     * Returns the last modification date as a unix timestamp.
     *
     * @return void
     */
    public function getLastModified()
    {
        $contacts = Auth::user()->account
                        ->contacts()
                        ->real()
                        ->active()
                        ->get();

        return $contacts->max('updated_at');
    }
}
