<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroySession;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheCurrentSession;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroySessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_device_out_and_forgets_every_remembered_device(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'remember_token' => 'dundermifflin']);
        $this->insertSession(id: 'stamford', user: $user);

        new DestroySession(user: $user, sessionId: 'stamford', currentSessionId: 'scranton')->execute();

        $this->assertDatabaseMissing('sessions', ['id' => 'stamford']);
        $this->assertNotSame('dundermifflin', $user->fresh()->remember_token);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::SessionRemoved
                && $job->account->id === $account->id
                && $job->user->id === $user->id
                && $job->parameters === ['device' => 'Safari on iPhone'],
        );
    }

    #[Test]
    public function it_refuses_a_session_that_belongs_to_somebody_else(): void
    {
        Queue::fake();

        $michael = User::factory()->create(['remember_token' => 'dundermifflin']);
        $dwight = User::factory()->create();
        $this->insertSession(id: 'schrute-farms', user: $dwight);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DestroySession(user: $michael, sessionId: 'schrute-farms', currentSessionId: 'scranton')->execute();
        } finally {
            $this->assertDatabaseHas('sessions', ['id' => 'schrute-farms']);
            $this->assertSame('dundermifflin', $michael->fresh()->remember_token);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_the_session_of_the_device_in_hand(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->insertSession(id: 'scranton', user: $user);

        $this->expectException(CannotDeleteTheCurrentSession::class);

        try {
            new DestroySession(user: $user, sessionId: 'scranton', currentSessionId: 'scranton')->execute();
        } finally {
            $this->assertDatabaseHas('sessions', ['id' => 'scranton']);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_a_session_already_removed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->insertSession(id: 'stamford', user: $user);

        new DestroySession(user: $user, sessionId: 'stamford', currentSessionId: 'scranton')->execute();

        $this->expectException(ModelNotFoundException::class);

        new DestroySession(user: $user, sessionId: 'stamford', currentSessionId: 'scranton')->execute();
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
