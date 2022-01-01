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
     * @param  string|null  $collectionId
     * @param  string  $uuid
     * @return mixed
     */
    public function getObjectUuid($collectionId, $uuid);

    /**
     * Returns the collection of objects.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection
     */
    public function getObjects($collectionId);

    /**
     * Returns the extension for this backend.
     *
     * @return string
     */
    public function getExtension();
}
