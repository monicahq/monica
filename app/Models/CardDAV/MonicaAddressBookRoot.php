<?php

namespace App\Models\CardDAV;

use Sabre\CardDAV\AddressBookRoot;

class MonicaAddressBookRoot extends AddressBookRoot
{
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
        return new MonicaAddressBookHome($this->carddavBackend, $principal['uri']);
    }
}
