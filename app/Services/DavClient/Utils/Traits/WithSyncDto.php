<?php

namespace App\Services\DavClient\Utils\Traits;

use App\Services\DavClient\Utils\Model\SyncDto;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

trait WithSyncDto
{
    /**
     * @var SyncDto
     */
    protected $sync;

    /**
     * Get carddav backend.
     *
     * @return CardDAVBackend
     */
    protected function backend(): CardDAVBackend
    {
        return $this->sync->backend();
    }
}
