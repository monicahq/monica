<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConsumeMagicLink;
use App\Jobs\CheckLastLogin;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsumeMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

    private function linkFor(User $user, array $overrides = []): MagicLink
    {
        return MagicLink::factory()->create(array_merge([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ], $overrides));
    }

    #[Test]
    public function it_signs_the_owner_of_the_link_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $link = $this->linkFor($user);

        $result = new ConsumeMagicLink(
            token: self::TOKEN,
            ip: '10.0.0.1',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertNotNull($user->refresh()->last_login_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: CheckLastLogin::class,
            callback: fn (CheckLastLogin $job): bool => $job->ip === '10.0.0.1',
        );
    }

    #[Test]
    public function it_warns_the_user_that_somebody_got_in_without_a_password(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();
        $link = $this->linkFor($user);

        new ConsumeMagicLink(
            token: self::TOKEN,
            ip: '10.0.0.1',
        )->execute();

        Notification::assertSentTo(
            $user,
            NewLoginDetected::class,
            fn (NewLoginDetected $notification): bool => $notification->ip === '10.0.0.1'
                && $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_burns_the_link_so_it_cannot_be_used_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $link = $this->linkFor($user);

        new ConsumeMagicLink(token: self::TOKEN)->execute();

        $this->assertNotNull($link->refresh()->used_at);

        $this->expectException(ModelNotFoundException::class);

        new ConsumeMagicLink(token: self::TOKEN)->execute();
    }

    #[Test]
    public function it_refuses_a_link_that_ran_out_of_time(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create(), ['expires_at' => now()->subMinute()]);

        $this->expectException(ModelNotFoundException::class);

        new ConsumeMagicLink(token: self::TOKEN)->execute();
    }

    #[Test]
    public function it_refuses_a_link_it_has_never_seen(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        new ConsumeMagicLink(token: self::TOKEN)->execute();
    }

    #[Test]
    public function it_refuses_a_link_belonging_to_a_suspended_user(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->inactive()->create());

        $this->expectException(ModelNotFoundException::class);

        new ConsumeMagicLink(token: self::TOKEN)->execute();
    }

    #[Test]
    public function it_refuses_a_token_that_does_not_match_the_link_it_names(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new ConsumeMagicLink(token: 'somebody-elses-token')->execute();
    }

    #[Test]
    public function it_leaves_a_link_it_refused_usable_by_its_owner(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create());

        try {
            new ConsumeMagicLink(token: 'a-token-nobody-holds')->execute();
        } catch (ModelNotFoundException) {
        }

        $this->assertNull($link->refresh()->used_at);
    }
}
