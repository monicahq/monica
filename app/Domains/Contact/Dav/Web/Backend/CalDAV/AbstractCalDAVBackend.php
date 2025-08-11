<?php

namespace App\Domains\Contact\Dav\Web\Backend\CalDAV;

use App\Domains\Contact\Dav\Web\Backend\IDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\SyncDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\WithUser;
use App\Domains\Contact\Dav\Web\DAVACL\PrincipalBackend;
use App\Models\Vault;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAV\Server as SabreServer;
use Sabre\DAV\Sync\Plugin as DAVSyncPlugin;

abstract class AbstractCalDAVBackend implements ICalDAVBackend, IDAVBackend
{
    use SyncDAVBackend;
    use WithUser;

    protected Vault $vault;

    public function withVault(Vault $vault): self
    {
        $this->vault = $vault;

        return $this;
    }

    /**
     * Get description array.
     */
    public function getDescription(): array
    {
        $token = DAVSyncPlugin::SYNCTOKEN_PREFIX.$this->refreshSyncToken($this->vault->id)->id;

        return [
            'id' => $this->backendId(),
            'uri' => $this->backendUri(),
            'principaluri' => PrincipalBackend::getPrincipalUser($this->user),
            '{DAV:}sync-token' => $token,
            '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token,
            '{'.CalDAVPlugin::NS_CALENDARSERVER.'}getctag' => $token,
        ];
    }

    /**
     * Get the new exported version of the object.
     */
    abstract protected function refreshObject(mixed $obj): string;

    /**
     * Extension for Calendar objects.
     */
    public function getExtension(): string
    {
        return '.ics';
    }

    /**
     * Datas for this object.
     */
    public function exportData(mixed $obj): array
    {
        $calendardata = $this->refreshObject($obj);
        if (! $obj->uuid) {
            $obj->refresh();
        }

        return [
            'id' => $obj->id,
            'uri' => $this->encodeUri($obj),
            'calendardata' => $calendardata,
            'etag' => '"'.hash('sha256', $calendardata).'"',
            'lastmodified' => $obj->updated_at->timestamp,
        ];
    }
}
