<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Exceptions\SnapshotIsImmutable;
use App\Models\RecordSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_to_have_the_state_it_captured_rewritten(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $this->expectException(SnapshotIsImmutable::class);

        $snapshot->payload = ['record' => ['first_name' => 'Somebody else']];
        $snapshot->save();
    }

    #[Test]
    public function it_lets_the_note_of_when_it_was_used_to_restore_be_written(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $snapshot->restored_at = now();
        $snapshot->save();

        $this->assertNotNull($snapshot->fresh()->restored_at);
    }

    #[Test]
    public function it_refuses_to_be_deleted(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $this->expectException(SnapshotIsImmutable::class);

        $snapshot->delete();
    }

    #[Test]
    public function it_names_every_file_a_live_snapshot_still_points_at(): void
    {
        RecordSnapshot::factory()->holding([4, 8])->create();
        RecordSnapshot::factory()->holding([8, 15])->create();

        $this->assertEqualsCanonicalizing([4, 8, 15], RecordSnapshot::fileIdsInUse());
    }

    #[Test]
    public function it_names_no_file_when_no_snapshot_points_at_one(): void
    {
        RecordSnapshot::factory()->create();

        $this->assertEquals([], RecordSnapshot::fileIdsInUse());
    }
}
