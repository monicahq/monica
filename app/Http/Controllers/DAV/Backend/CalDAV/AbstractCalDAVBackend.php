<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use App\Models\User\User;
use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAV\Sync\Plugin as DAVSyncPlugin;
use App\Http\Controllers\DAV\Backend\IDAVBackend;
use App\Http\Controllers\DAV\Backend\SyncDAVBackend;
use App\Http\Controllers\DAV\DAVACL\PrincipalBackend;

abstract class AbstractCalDAVBackend implements ICalDAVBackend, IDAVBackend
{
    use SyncDAVBackend;

    /**
     * Create a new instance of AbstractCalDAVBackend.
     *
     * @param  User  $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get description array.
     *
     * @return array
     */
    public function getDescription()
    {
        $token = DAVSyncPlugin::SYNCTOKEN_PREFIX.$this->refreshSyncToken(null)->id;

        return [
            'id' => $this->backendUri(),
            'uri' => $this->backendUri(),
            'principaluri' => PrincipalBackend::getPrincipalUser($this->user),
            '{DAV:}sync-token'  => $token,
            '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token,
            '{'.CalDAVPlugin::NS_CALENDARSERVER.'}getctag' => $token,
        ];
    }

    /**
     * Get the new exported version of the object.
     *
     * @param  mixed  $obj
     * @return string
     */
    abstract protected function refreshObject($obj): string;
}
