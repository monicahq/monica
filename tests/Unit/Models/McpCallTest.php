<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\McpCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCallTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prunes_the_calls_older_than_the_retention(): void
    {
        $jim = User::factory()->create();

        $old = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(91)]);
        $recent = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(89)]);

        $this->artisan('model:prune', ['--model' => [McpCall::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    #[Test]
    public function it_keeps_at_least_a_day_when_the_retention_is_set_to_nothing(): void
    {
        config(['monica.mcp_call_retention_in_days' => 0]);
        $jim = User::factory()->create();

        $yesterday = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(2)]);
        $anHourAgo = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subHour()]);

        $this->artisan('model:prune', ['--model' => [McpCall::class]])->assertSuccessful();

        $this->assertModelMissing($yesterday);
        $this->assertModelExists($anHourAgo);
    }

    #[Test]
    public function it_narrows_the_calls_to_a_period_counted_back_from_now(): void
    {
        $jim = User::factory()->create();

        $today = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subHours(2)]);
        $lastWeek = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(5)]);
        $lastMonth = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(20)]);
        $older = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'started_at' => now()->subDays(60)]);

        $this->assertSame([$today->id], McpCall::query()->matching('day', null, null, null)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$today->id, $lastWeek->id], McpCall::query()->matching('week', null, null, null)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$today->id, $lastWeek->id, $lastMonth->id], McpCall::query()->matching('month', null, null, null)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$today->id, $lastWeek->id, $lastMonth->id, $older->id], McpCall::query()->matching('all', null, null, null)->pluck('id')->all());
    }

    #[Test]
    public function it_narrows_the_calls_to_one_connection_one_result_and_one_tool(): void
    {
        $jim = User::factory()->create();

        $key = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => '14']);
        $otherKey = McpCall::factory()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => '15']);
        $refused = McpCall::factory()->refused()->create(['account_id' => $jim->account_id, 'user_id' => $jim->id, 'connection_id' => '14', 'tool' => 'delete-life-event']);

        $this->assertEqualsCanonicalizing([$key->id, $refused->id], McpCall::query()->matching(null, 'api_key:14', null, null)->pluck('id')->all());
        $this->assertSame([], McpCall::query()->matching(null, 'oauth_client:14', null, null)->pluck('id')->all());
        $this->assertSame([$refused->id], McpCall::query()->matching(null, null, 'refused', null)->pluck('id')->all());
        $this->assertSame([$refused->id], McpCall::query()->matching(null, null, null, 'delete-life-event')->pluck('id')->all());
        $this->assertSame([$refused->id], McpCall::query()->matching('day', 'api_key:14', 'refused', 'delete-life-event')->pluck('id')->all());
        $this->assertSame([$otherKey->id], McpCall::query()->matching(null, 'api_key:15', 'succeeded', 'get-contact')->pluck('id')->all());
    }
}
