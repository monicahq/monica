<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserActionEnum;
use App\Exceptions\LogIsAppendOnly;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_name_of_whoever_performed_the_action(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        $log = Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals('Michael Scott', $log->author);
    }

    #[Test]
    public function it_falls_back_to_the_recorded_email_when_the_user_is_gone(): void
    {
        $log = Log::factory()->create([
            'user_id' => null,
            'user_email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertEquals('michael.scott@dundermifflin.com', $log->author);
    }

    #[Test]
    public function it_falls_back_to_the_recorded_email_when_the_user_is_only_soft_deleted(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $log = Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'user_email' => 'michael.scott@dundermifflin.com',
        ]);

        $user->delete();

        $log->refresh();

        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('michael.scott@dundermifflin.com', $log->author);
    }

    #[Test]
    public function it_names_the_application_when_nobody_was_behind_the_action(): void
    {
        $log = Log::factory()->bySystem()->create();

        $this->assertTrue($log->wasPerformedBySystem());
        $this->assertNull($log->user_id);
        $this->assertEquals('The system', $log->author);
    }

    #[Test]
    public function it_knows_a_user_was_behind_the_action(): void
    {
        $log = Log::factory()->create(['user_email' => 'michael.scott@dundermifflin.com']);

        $this->assertFalse($log->wasPerformedBySystem());
    }

    #[Test]
    public function it_outlives_the_object_the_action_was_performed_on(): void
    {
        $user = User::factory()->create();
        $log = Log::factory()->create([
            'account_id' => $user->account_id,
            'loggable_type' => $user->getMorphClass(),
            'loggable_id' => $user->id,
        ]);

        $user->forceDelete();

        $log->refresh();

        $this->assertNull($log->loggable);
        $this->assertEquals($user->id, $log->loggable_id);
    }

    #[Test]
    public function it_refuses_to_be_changed_once_it_is_written(): void
    {
        $log = Log::factory()->create(['action' => UserActionEnum::UserLogin->value]);

        $this->expectException(LogIsAppendOnly::class);

        try {
            $log->update(['action' => UserActionEnum::AccountCreation->value]);
        } finally {
            $this->assertEquals(UserActionEnum::UserLogin->value, $log->fresh()->action);
        }
    }

    #[Test]
    public function it_refuses_to_be_deleted(): void
    {
        $log = Log::factory()->create();

        $this->expectException(LogIsAppendOnly::class);

        try {
            $log->delete();
        } finally {
            $this->assertModelExists($log);
        }
    }

    #[Test]
    public function it_describes_the_action_with_its_parameters(): void
    {
        $log = Log::factory()->create([
            'action' => UserActionEnum::UserProfileUpdate->value,
            'parameters' => ['name' => 'Michael Scott'],
        ]);

        $this->assertEquals('Updated the profile of Michael Scott', $log->description);
    }

    #[Test]
    public function it_describes_an_action_that_carries_no_parameter(): void
    {
        $log = Log::factory()->create([
            'action' => UserActionEnum::UserLogin->value,
            'parameters' => null,
        ]);

        $this->assertEquals('Signed in', $log->description);
    }

    #[Test]
    public function it_describes_an_action_that_counts_something_in_the_singular(): void
    {
        $log = Log::factory()->create([
            'action' => UserActionEnum::OrphanedFilesSwept->value,
            'parameters' => ['count' => 1],
        ]);

        $this->assertEquals('Removed 1 file nothing pointed at any more', $log->description);
    }

    #[Test]
    public function it_describes_an_action_that_counts_something_in_the_plural(): void
    {
        $log = Log::factory()->create([
            'action' => UserActionEnum::OrphanedFilesSwept->value,
            'parameters' => ['count' => 4],
        ]);

        $this->assertEquals('Removed 4 files nothing pointed at any more', $log->description);
    }

    #[Test]
    public function it_describes_a_counted_action_that_also_carries_another_parameter(): void
    {
        $log = Log::factory()->create([
            'action' => UserActionEnum::FieldValuesDetached->value,
            'parameters' => ['count' => 1, 'label' => 'Nickname'],
        ]);

        $this->assertEquals('Marked 1 value written in Nickname as unused', $log->description);
    }

    #[Test]
    public function it_falls_back_to_the_raw_name_of_an_action_it_does_not_know(): void
    {
        $log = Log::factory()->create([
            'action' => 'stapler_put_in_jelly',
            'parameters' => null,
        ]);

        $this->assertEquals('stapler_put_in_jelly', $log->description);
    }
}
