<?php

namespace App\Domains\Contact\Dav\Web\Backend\CardDAV;

use Sabre\CardDAV\AddressBook as BaseAddressBook;

class AddressBook extends BaseAddressBook
{
    /**
     * Returns the last modification date.
     */
    public function getLastModified(): ?int
    {
        $carddavBackend = $this->carddavBackend;
        if ($carddavBackend instanceof CardDAVBackend) {
            $date = $carddavBackend->getLastModified($this->addressBookInfo['id']);
            if (! is_null($date)) {
                return (int) $date->timestamp;
            }
        }

        return null;
    }

    /**
     * This method returns the current sync-token for this collection.
     * This can be any string.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     */
    public function getSyncToken(): ?string
    {
        if ($this->carddavBackend instanceof CardDAVBackend) {
            return (string) $this->carddavBackend->refreshSyncToken($this->addressBookInfo['id'])->id;
        }

        return null;
    }
}
