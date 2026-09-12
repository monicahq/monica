<?php

declare(strict_types=1);

namespace App\Http\Dav;

use Override;
use Sabre\CalDAV\Calendar;
use Sabre\CalDAV\Plugin;

class ReadOnlyCalendar extends Calendar
{
    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getACL(): array
    {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{'.Plugin::NS_CALDAV.'}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
    }
}
