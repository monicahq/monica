<?php

namespace App\Models\DAV\Backends;

use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Auth;

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
     * @param string  $uri
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
