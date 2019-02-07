<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAV\Sync\Plugin as DAVSyncPlugin;
use App\Http\Controllers\DAV\Backend\IDAVBackend;
use App\Http\Controllers\DAV\Backend\SyncDAVBackend;
use App\Http\Controllers\DAV\DAVACL\PrincipalBackend;

abstract class AbstractCalDAVBackend implements ICalDAVBackend, IDAVBackend
{
    use SyncDAVBackend;

    public function getDescription()
    {
        $token = $this->getCurrentSyncToken();

        $des = [
            'id' => $this->backendUri(),
            'uri' => $this->backendUri(),
            'principaluri' => PrincipalBackend::getPrincipalUser(),
        ];
        if ($token) {
            $token = DAVSyncPlugin::SYNCTOKEN_PREFIX.$token->id;
            $des += [
                '{DAV:}sync-token'  => $token,
                '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token,
                '{'.CalDAVPlugin::NS_CALENDARSERVER.'}getctag' => $token,
            ];
        }

        return $des;
    }
}
