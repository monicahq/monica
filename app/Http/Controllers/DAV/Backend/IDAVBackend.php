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
     * @param string  $uuid
     * @return mixed
     */
    public function getObjectUuid($uuid);

    /**
     * Returns the collection of objects.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects();

    /**
     * Returns the extension for this backend.
     *
     * @return string
     */
    public function getExtension();
}
