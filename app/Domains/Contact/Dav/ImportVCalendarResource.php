<?php

namespace App\Domains\Contact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCalendar;
use Sabre\VObject\Component\VCalendar;

interface ImportVCalendarResource
{
    /**
     * Set context.
     */
    public function setContext(ImportVCalendar $context): self;

    /**
     * Test if the Calendar is handled by this importer.
     */
    public function handle(VCalendar $vcalendar): bool;

    /**
     * Can import Calendar.
     */
    public function can(VCalendar $vcalendar): bool;

    /**
     * Import Calendar.
     */
    public function import(VCalendar $vcalendar, ?VCalendarResource $result): ?VCalendarResource;
}
