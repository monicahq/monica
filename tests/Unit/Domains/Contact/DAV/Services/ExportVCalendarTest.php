<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\ExportVCalendar;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportVCalendarTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;
    use PHPUnitAssertions;

    #[Test]
    #[Group('dav')]
    public function it_exports_a_task()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $task = ContactTask::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $vCard = (new ExportVCalendar($this->app))->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'contact_task_id' => $task->id,
        ]);

        $this->assertInstanceOf(VCalendar::class, $vCard);
        $this->assertEquals($this->getTask($task), $vCard->serialize());
    }

    #[Test]
    #[Group('dav')]
    public function it_exports_a_date()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $vCard = (new ExportVCalendar($this->app))->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'contact_important_date_id' => $date->id,
        ]);

        $this->assertInstanceOf(VCalendar::class, $vCard);
        $this->assertEquals($this->getDate($date), $vCard->serialize());
    }
}
