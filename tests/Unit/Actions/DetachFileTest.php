<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachFile;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetachFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_one_fewer_thing_pointing_at_the_file(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->used(3)->create(['account_id' => $user->account_id]);

        $result = new DetachFile(user: $user, file: $file)->execute();

        $this->assertInstanceOf(File::class, $result);
        $this->assertEquals(2, $file->fresh()->reference_count);
        $this->assertNull($file->fresh()->orphaned_at);
    }

    /**
     * Letting go never deletes. The moment the last thing let go is written
     * down instead, and the sweep comes for it later.
     */
    #[Test]
    public function it_starts_the_grace_period_when_the_last_thing_lets_go(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->used(1)->create(['account_id' => $user->account_id]);

        new DetachFile(user: $user, file: $file)->execute();

        $file->refresh();

        $this->assertEquals(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);
        $this->assertModelExists($file);
    }

    #[Test]
    public function it_never_counts_below_nothing(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create(['account_id' => $user->account_id]);

        new DetachFile(user: $user, file: $file)->execute();

        $this->assertEquals(0, $file->fresh()->reference_count);
    }

    #[Test]
    public function it_refuses_a_file_in_another_account(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->used()->create();

        $this->expectException(ModelNotFoundException::class);

        new DetachFile(user: $user, file: $file)->execute();
    }
}
