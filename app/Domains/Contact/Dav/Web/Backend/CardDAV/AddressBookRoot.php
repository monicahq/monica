<?php

namespace App\Domains\Contact\Dav\Web\Backend\CardDAV;

use Sabre\CardDAV\AddressBookRoot as BaseAddressBookRoot;
use Sabre\DAV\INode;

class AddressBookRoot extends BaseAddressBookRoot
{
    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     *
     * @psalm-suppress ParamNameMismatch
     */
    public function getChildForPrincipal(array $principal): INode
    {
        return new AddressBookHome($this->carddavBackend, $principal['uri']);
    }
}
