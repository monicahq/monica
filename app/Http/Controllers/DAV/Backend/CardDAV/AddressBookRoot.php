<?php

namespace App\Http\Controllers\DAV\Backend\CardDAV;

use Sabre\DAVACL\IACL;
use Sabre\DAVACL\ACLTrait;
use Sabre\CardDAV\AddressBookRoot as BaseAddressBookRoot;

class AddressBookRoot extends BaseAddressBookRoot implements IACL
{
    use ACLTrait;

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
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @param array $principal
     * @return \Sabre\DAV\INode
     */
    public function getChildForPrincipal(array $principal)
    {
        return new AddressBookHome($this->carddavBackend, $principal['uri']);
    }
}
