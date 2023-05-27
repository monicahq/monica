<?php

namespace App\Domains\Contact\Dav\Web\Backend\CardDAV;

use Sabre\CardDAV\AddressBookHome as BaseAddressBookHome;

class AddressBookHome extends BaseAddressBookHome
{
    /**
     * Principal uri.
     */
    private function getPrincipalUri(): string
    {
        /** @var string $principalUri */
        $principalUri = $this->principalUri;

        return $principalUri;
    }

    /**
     * Returns a list of addressbooks.
     */
    public function getChildren(): array
    {
        return collect($this->carddavBackend->getAddressBooksForUser($this->getPrincipalUri()))
            ->map(fn (array $addressBookInfo) => new AddressBook($this->carddavBackend, $addressBookInfo))
            ->toArray();
    }
}
