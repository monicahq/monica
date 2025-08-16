<?php

namespace Tests\Unit\Domains\Contact\ManageTasks\Dav;

use App\Domains\Contact\Dav\Services\ImportVCalendar;
use App\Domains\Contact\ManageTasks\Dav\ImportContactTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportContactTaskTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_uuid_default()
    {
        $importContact = new ImportContactTask;
        $importContact->setContext(new ImportVCalendar($this->app));

        $vcalendar = new VCalendar;
        $vcalendar->add('VTODO', [
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $data = [];
        $data = $this->invokePrivateMethod($importContact, 'importUid', [$data, $vcalendar]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $data['id']);
    }
}
