<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AttachFile;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_one_more_thing_pointing_at_the_file(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->used(2)->create(['account_id' => $user->account_id]);

        $result = new AttachFile(user: $user, file: $file)->execute();

        $this->assertInstanceOf(File::class, $result);
        $this->assertEquals(3, $file->fresh()->reference_count);
    }

    #[Test]
    public function it_stops_the_grace_period_of_a_file_somebody_picked_up_again(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create(['account_id' => $user->account_id, 'orphaned_at' => now()->subDays(3)]);

        new AttachFile(user: $user, file: $file)->execute();

        $this->assertEquals(1, $file->fresh()->reference_count);
        $this->assertNull($file->fresh()->orphaned_at);
    }

    #[Test]
    public function it_refuses_a_file_in_another_account(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new AttachFile(user: $user, file: $file)->execute();
    }
}
