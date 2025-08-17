<?php

namespace App\Domains\Contact\Dav\Web\Backend\CalDAV;

use App\Domains\Contact\Dav\IDavResource;
use App\Domains\Contact\Dav\Jobs\UpdateVCalendar;
use App\Domains\Contact\Dav\Services\GetEtag;
use App\Domains\Contact\Dav\VCalendarResource;
use App\Domains\Contact\Dav\Web\Backend\IDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\SyncDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\WithUser;
use App\Domains\Contact\Dav\Web\DAVACL\PrincipalBackend;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
        $token = DAVSyncPlugin::SYNCTOKEN_PREFIX.$this->refreshSyncToken($this->backendId())->id;

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
    abstract protected function refreshObject(IDavResource $obj): string;

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
    public function exportData(VCalendarResource $resource): array
    {
        $calendardata = $resource->vcalendar;
        try {
            if ($calendardata) {
                $timestamp = $this->lastModified($calendardata);
            }

            if ($calendardata === null || empty($calendardata) || $timestamp === null || $timestamp < $resource->updated_at) {
                $calendardata = $this->refreshObject($resource);
            }

            $etag = (new GetEtag)->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $this->vault->id,
                'vcalendar' => $resource->refresh(),
            ]);

            return [
                'id' => $resource->uuid,
                'uri' => $this->encodeUri($resource),
                'calendardata' => $calendardata,
                'etag' => $etag,
                'distant_etag' => $resource->distant_etag,
                'lastmodified' => $resource->updated_at->timestamp,
            ];
        } catch (\Exception $e) {
            Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                'calendardata' => $calendardata,
                'id' => $resource->id,
                $e,
            ]);
            throw $e;
        }
    }

    abstract protected function lastModified(string $card): ?Carbon;

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     */
    public function updateOrCreateCalendarObject(?string $calendarId, ?string $objectUri, ?string $calendarData): ?string
    {
        return (new UpdateVCalendar)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'uri' => $objectUri,
            'calendar' => $calendarData,
        ]);
    }
}
