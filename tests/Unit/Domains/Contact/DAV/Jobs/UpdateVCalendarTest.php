<?php

namespace Tests\Unit\Domains\Contact\DAV\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCalendar;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class UpdateVCalendarTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    #[Test]
    #[Group('dav')]
    public function it_creates_a_task()
    {
        $user = User::factory()->create();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $calendar = 'BEGIN:VCALENDAR
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20250729T000000Z
LAST-MODIFIED:20250814T000000Z
DTSTAMP:20250814T000000Z
UID:3a7baf23-50b6-43dd-b441-7d70362f6356
SUMMARY:My Task
END:VTODO
END:VCALENDAR
';
        $etag = '"'.hash('sha256', $calendar).'"';

        $data = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'uri' => 'https://test/dav/3a7baf23-50b6-43dd-b441-7d70362f6356',
            'etag' => $etag,
            'calendar' => $calendar,
            'external' => true,
        ];

        (new UpdateVCalendar)->execute($data);

        $this->assertDatabaseHas('contact_tasks', [
            'label' => 'My Task',
            'vcalendar' => $calendar,
            'distant_etag' => $etag,
            'distant_uuid' => '3a7baf23-50b6-43dd-b441-7d70362f6356',
        ]);
    }

    #[Test]
    #[Group('dav')]
    public function it_creates_a_date()
    {
        $user = User::factory()->create();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $calendar = 'BEGIN:VCALENDAR
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
VERSION:2.0
BEGIN:VEVENT
CREATED:20250814T000000Z
LAST-MODIFIED:20250814T000000Z
DTSTAMP:20250814T000000Z
UID:36ee6e82-5262-404f-aea1-98859c631892
SUMMARY:Réunion importante
DTSTART;VALUE=DATE:20250813
DTEND;VALUE=DATE:20250814
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR
';
        $etag = '"'.hash('sha256', $calendar).'"';

        $data = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'uri' => 'https://test/dav/36ee6e82-5262-404f-aea1-98859c631892',
            'etag' => $etag,
            'calendar' => $calendar,
            'external' => true,
        ];

        (new UpdateVCalendar)->execute($data);

        $this->assertDatabaseHas('contact_important_dates', [
            'label' => 'Réunion importante',
            'vcalendar' => $calendar,
            'distant_etag' => $etag,
            'distant_uuid' => '36ee6e82-5262-404f-aea1-98859c631892',
        ]);
    }
}
