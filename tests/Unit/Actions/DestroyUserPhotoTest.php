<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyUserPhoto;
use App\Actions\UpdateUserPhoto;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyUserPhotoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_the_photo_and_its_files(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create([
            'first_name' => 'Kevin',
            'last_name' => 'Malone',
        ]);

        $withPhoto = new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('chili.jpg', 400, 400),
        )->execute();

        $path = (string) $withPhoto->photo_path;

        $result = new DestroyUserPhoto(user: $user->fresh())->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertNull($result->photo_path);
        $this->assertFalse($result->hasPhoto());

        foreach (User::photoPixelSizes() as $pixels) {
            Storage::disk('local')->assertMissing($path.'_'.$pixels.'.webp');
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'photo_path' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPhotoDeletion,
        );
    }

    #[Test]
    public function it_does_nothing_when_there_is_no_photo(): void
    {
        Queue::fake();

        $user = User::factory()->create(['photo_path' => null]);

        $result = new DestroyUserPhoto(user: $user)->execute();

        $this->assertNull($result->photo_path);

        Queue::assertNotPushed(LogUserAction::class);
    }
}
