<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Services\ImportVCalendar;
use App\Domains\Contact\ManageContactImportantDates\Dav\ImportCalendarContactImportantDates;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportCalendarContactImportantDatesTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_uuid_default()
    {
        $importContact = new ImportCalendarContactImportantDates;
        $importContact->setContext(new ImportVCalendar($this->app));

        $vcalendar = new VCalendar;
        $vcalendar->add('VEVENT', [
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $data = [];
        $data = $this->invokePrivateMethod($importContact, 'importUid', [$data, $vcalendar]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $data['id']);
    }
}
