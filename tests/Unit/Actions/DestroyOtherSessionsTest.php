<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyOtherSessions;
use App\Enums\UserActionEnum;
use App\Exceptions\PasswordIsWrong;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyOtherSessionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_every_other_device_out_and_keeps_the_one_in_hand(): void
    {
        Queue::fake();
        Event::fake([OtherDeviceLogout::class]);
        config(['session.driver' => 'database']);

        $account = Account::factory()->create();
        $michael = User::factory()->create(['account_id' => $account->id, 'password' => Hash::make('worlds-best-boss')]);
        $dwight = User::factory()->create();
        $passwordHash = $michael->password;
        $this->insertSession(id: 'scranton', user: $michael);
        $this->insertSession(id: 'stamford', user: $michael);
        $this->insertSession(id: 'nashua', user: $michael);
        $this->insertSession(id: 'schrute-farms', user: $dwight);

        $this->actingAs($michael);

        new DestroyOtherSessions(user: $michael, password: 'worlds-best-boss', currentSessionId: 'scranton')->execute();

        $this->assertDatabaseHas('sessions', ['id' => 'scranton']);
        $this->assertDatabaseMissing('sessions', ['id' => 'stamford']);
        $this->assertDatabaseMissing('sessions', ['id' => 'nashua']);
        $this->assertDatabaseHas('sessions', ['id' => 'schrute-farms']);
        $this->assertNotSame($passwordHash, $michael->fresh()->password);
        $this->assertTrue(Hash::check('worlds-best-boss', $michael->fresh()->password));
        Event::assertDispatched(OtherDeviceLogout::class, fn (OtherDeviceLogout $event): bool => $event->user->is($michael));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OtherSessionsRemoved
                && $job->account->id === $account->id
                && $job->user->id === $michael->id,
        );
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::SessionRemoved
                && $job->parameters === ['device' => 'Unknown device'],
        );
        Queue::assertPushed(LogUserAction::class, 3);
    }

    #[Test]
    public function it_refuses_a_wrong_password(): void
    {
        Queue::fake();
        config(['session.driver' => 'database']);

        $michael = User::factory()->create(['password' => Hash::make('worlds-best-boss')]);
        $passwordHash = $michael->password;
        $this->insertSession(id: 'stamford', user: $michael);

        $this->actingAs($michael);

        $this->expectException(PasswordIsWrong::class);

        try {
            new DestroyOtherSessions(user: $michael, password: 'bears-beets-battlestar', currentSessionId: 'scranton')->execute();
        } finally {
            $this->assertDatabaseHas('sessions', ['id' => 'stamford']);
            $this->assertSame($passwordHash, $michael->fresh()->password);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_an_account_without_a_password(): void
    {
        Queue::fake();
        config(['session.driver' => 'database']);

        $ryan = User::factory()->create(['password' => null]);
        $this->insertSession(id: 'wuphf', user: $ryan);

        $this->actingAs($ryan);

        $this->expectException(PasswordIsWrong::class);

        try {
            new DestroyOtherSessions(user: $ryan, password: '', currentSessionId: 'scranton')->execute();
        } finally {
            $this->assertDatabaseHas('sessions', ['id' => 'wuphf']);
            Queue::assertNothingPushed();
        }
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => null,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
