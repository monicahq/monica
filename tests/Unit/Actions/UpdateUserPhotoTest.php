<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserPhoto;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserPhotoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_the_photo_and_logs_the_action(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $result = new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('dwight.jpg', 400, 400),
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertStringStartsWith('photos/'.$user->id.'/', (string) $result->photo_path);

        // The name somebody gave the file never reaches the disk, and the base
        // path carries no extension of its own.
        $this->assertStringNotContainsString('dwight', (string) $result->photo_path);
        $this->assertStringNotContainsString('.webp', (string) $result->photo_path);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'photo_path' => $result->photo_path,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPhotoUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_writes_a_square_version_at_one_and_two_times_the_displayed_size(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $result = new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('michael.jpg', 800, 200),
        )->execute();

        foreach (User::photoPixelSizes() as $pixels) {
            $path = $result->photoVariantPath($pixels);

            Storage::disk('local')->assertExists($path);

            $size = getimagesizefromstring((string) Storage::disk('local')->get($path));

            $this->assertEquals($pixels, $size[0]);
            $this->assertEquals($pixels, $size[1]);
            $this->assertEquals('image/webp', $size['mime']);
        }

        $this->assertEquals([96, 192], User::photoPixelSizes());
    }

    #[Test]
    public function it_removes_the_files_of_the_previous_photo(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $first = new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('pam.jpg', 400, 400),
        )->execute();

        $firstPath = (string) $first->photo_path;

        $second = new UpdateUserPhoto(
            user: $user->fresh(),
            file: UploadedFile::fake()->image('jim.jpg', 400, 400),
        )->execute();

        $this->assertNotEquals($firstPath, $second->photo_path);

        foreach (User::photoPixelSizes() as $pixels) {
            Storage::disk('local')->assertMissing($firstPath.'_'.$pixels.'.webp');
            Storage::disk('local')->assertExists($second->photoVariantPath($pixels));
        }
    }

    #[Test]
    public function it_stamps_when_the_profile_was_last_saved(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create(['last_saved_at' => null]);

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('angela.jpg', 400, 400),
        )->execute();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->refresh()->last_saved_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_image_we_accept(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_file_larger_than_five_megabytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('creed.jpg')->size(6 * 1024),
        )->execute();
    }
}
