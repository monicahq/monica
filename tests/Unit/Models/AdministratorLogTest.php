<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\LogIsAppendOnly;
use App\Models\AdministratorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_falls_back_to_the_address_when_the_administrator_is_gone(): void
    {
        $entry = AdministratorLog::factory()->create([
            'user_id' => null,
            'user_email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertEquals('michael.scott@dundermifflin.com', $entry->author);
    }

    #[Test]
    public function it_reads_as_the_console_when_the_command_line_acted(): void
    {
        $entry = AdministratorLog::factory()->byTheConsole()->create();

        $this->assertTrue($entry->wasPerformedByTheConsole());
    }

    #[Test]
    public function it_shows_an_action_it_no_longer_knows_about_by_its_raw_name(): void
    {
        $entry = AdministratorLog::factory()->create(['action' => 'something_from_another_version']);

        $this->assertEquals('something_from_another_version', $entry->description);
    }

    #[Test]
    public function it_cannot_be_changed_once_written(): void
    {
        $entry = AdministratorLog::factory()->create();

        $this->expectException(LogIsAppendOnly::class);

        $entry->update(['action' => AdministratorActionEnum::UserReactivated->value]);
    }

    #[Test]
    public function it_cannot_be_deleted(): void
    {
        $entry = AdministratorLog::factory()->create();

        $this->expectException(LogIsAppendOnly::class);

        $entry->delete();
    }
}
