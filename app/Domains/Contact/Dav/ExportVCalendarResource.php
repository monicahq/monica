<?php

namespace App\Domains\Contact\Dav;

use Sabre\VObject\Component\VCalendar;

/**
 * @template T of \App\Domains\Contact\Dav\VCalendarResource
 */
interface ExportVCalendarResource
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;

    /**
     * @param  T  $resource
     */
    public function export(mixed $resource, VCalendar $vcal): void;
}
