<?php

namespace App\Http\Controllers\DAV\Backend;

interface IDAVBackend
{
    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri();

    /**
     * Returns the object for the specific uuid.
     *
     * @param mixed|null $addressBookId
     * @param string  $uuid
     * @return mixed
     */
    public function getObjectUuid($addressBookId, $uuid);

    /**
     * Returns the collection of objects.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects($addressBookId);

    /**
     * Returns the extension for this backend.
     *
     * @return string
     */
    public function getExtension();
}
