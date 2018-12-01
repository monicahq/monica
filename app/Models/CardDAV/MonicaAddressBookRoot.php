<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
