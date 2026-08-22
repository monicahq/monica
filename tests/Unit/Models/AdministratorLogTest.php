<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\LogIsAppendOnly;
use App\Models\Account;
use App\Models\AdministratorLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_the_administrator_who_took_the_action(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $entry = AdministratorLog::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($entry->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $entry->user->email);
    }

    #[Test]
    public function it_belongs_to_the_account_the_action_was_taken_on(): void
    {
        $account = Account::factory()->create();
        $entry = AdministratorLog::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($entry->account()->exists());
        $this->assertTrue($account->is($entry->account));
    }

    #[Test]
    public function it_points_at_what_was_acted_on(): void
    {
        $user = User::factory()->create();
        $entry = AdministratorLog::factory()->create([
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
        ]);

        $this->assertTrue($user->is($entry->subject));
    }

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
        $this->assertEquals('The console', $entry->author);
    }

    #[Test]
    public function it_reads_the_sentence_of_the_action_with_its_parameters(): void
    {
        $entry = AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Michael Scott'],
        ]);

        $this->assertEquals('Deactivated Michael Scott', $entry->description);
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
