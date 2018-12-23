<?php

namespace App\Models\CardDAV;

use Sabre\CardDAV\AddressBookHome;

class MonicaAddressBookHome extends AddressBookHome
{
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
