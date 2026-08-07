<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $log = Log::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($log->account()->exists());
        $this->assertTrue($account->is($log->account));
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $log = Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($log->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $log->user->email);
    }

    #[Test]
    public function it_casts_the_parameters_to_an_array(): void
    {
        $log = Log::factory()->create(['parameters' => ['name' => 'Dunder Mifflin']]);

        $this->assertEquals(['name' => 'Dunder Mifflin'], $log->refresh()->parameters);
    }

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
    public function it_falls_back_to_the_raw_name_of_an_action_it_does_not_know(): void
    {
        $log = Log::factory()->create([
            'action' => 'stapler_put_in_jelly',
            'parameters' => null,
        ]);

        $this->assertEquals('stapler_put_in_jelly', $log->description);
    }
}
