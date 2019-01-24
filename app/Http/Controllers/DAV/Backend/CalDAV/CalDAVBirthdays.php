<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use Sabre\DAV;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use App\Services\VCalendar\ExportVCalendar;
use Sabre\DAV\Sync\Plugin as DAVSyncPlugin;
use App\Http\Controllers\DAV\DAVACL\PrincipalBackend;

class CalDAVBirthdays extends AbstractCalDAVBackend
{
    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri()
    {
        return 'birthdays';
    }

    public function getDescription()
    {
        $name = Auth::user()->name;
        $token = $this->getCurrentSyncToken();

        $des = [
            'principaluri'      => PrincipalBackend::getPrincipalUser(),
            '{DAV:}displayname' => $name,
            '{'.SabreServer::NS_SABREDAV.'}read-only' => 1,
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => 'Birthdays',
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => Auth::user()->timezone,
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

    /**
     * Extension for Calendar objects.
     *
     * @var string
     */
    public function getExtension()
    {
        return '.ics';
    }

    /**
     * Datas for this date.
     *
     * @param mixed $date
     * @return array
     */
    public function prepareData($date)
    {
        if ($date instanceof SpecialDate) {
            try {
                $vcal = app(ExportVCalendar::class)
                    ->execute([
                        'account_id' => Auth::user()->account_id,
                        'special_date_id' => $date->id,
                    ]);

                $calendardata = $vcal->serialize();

                return [
                    'id' => $date->id,
                    'uri' => $this->encodeUri($date),
                    'calendardata' => $calendardata,
                    'etag' => '"'.md5($calendardata).'"',
                    'lastmodified' => $date->updated_at->timestamp,
                ];
            } catch (\Exception $e) {
                Log::debug(__CLASS__.' prepareData: '.(string) $e);
            }
        }
    }

    private function hasBirthday($contact)
    {
        if (! $contact || ! $contact->birthdate) {
            return false;
        }
        $birthdayState = $contact->getBirthdayState();
        if ($birthdayState != 'almost' && $birthdayState != 'exact') {
            return false;
        }

        return true;
    }

    /**
     * Returns the date for the specific uri.
     *
     * @param string  $uri
     * @return mixed
     */
    public function getObjectUuid($uuid)
    {
        return SpecialDate::where([
            'account_id' => Auth::user()->account_id,
            'uuid' => $uuid,
        ])->first();
    }

    /**
     * Returns the collection of contact's birthdays.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects()
    {
        $contacts = Auth::user()->account
                    ->contacts()
                    ->real()
                    ->active()
                    ->get();

        return $contacts->filter(function ($contact) {
            return $this->hasBirthday($contact);
        })
        ->map(function ($contact) {
            return $contact->birthdate;
        });
    }

    public function updateOrCreateCalendarObject($objectUri, $calendarData)
    {
    }

    public function deleteCalendarObject($objectUri)
    {
    }
}
