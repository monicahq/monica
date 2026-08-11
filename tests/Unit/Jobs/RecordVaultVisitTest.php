<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\RecordVaultVisit;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordVaultVisitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_down_when_the_vault_was_opened(): void
    {
        $vault = Vault::factory()->create(['last_visited_at' => null]);

        RecordVaultVisit::dispatch(vault: $vault);

        $vault->refresh();

        $this->assertNotNull($vault->last_visited_at);
        $this->assertEqualsWithDelta(now()->timestamp, $vault->last_visited_at->timestamp, 2);
    }

    #[Test]
    public function it_takes_the_moment_the_vault_was_opened_rather_than_the_moment_the_queue_got_to_it(): void
    {
        $vault = Vault::factory()->create(['last_visited_at' => null]);
        $openedAt = now()->subMinutes(5);

        new RecordVaultVisit(vault: $vault, visitedAt: $openedAt)->handle();

        $vault->refresh();

        $this->assertEqualsWithDelta($openedAt->timestamp, $vault->last_visited_at->timestamp, 2);
    }

    #[Test]
    public function it_leaves_the_moment_the_vault_was_edited_alone(): void
    {
        $vault = Vault::factory()->create(['last_visited_at' => null]);
        $editedAt = now()->subWeek();

        Vault::withoutTimestamps(fn () => $vault->forceFill(['updated_at' => $editedAt])->saveQuietly());

        new RecordVaultVisit(vault: $vault)->handle();

        $vault->refresh();

        $this->assertEqualsWithDelta($editedAt->timestamp, $vault->updated_at->timestamp, 2);
    }
}
