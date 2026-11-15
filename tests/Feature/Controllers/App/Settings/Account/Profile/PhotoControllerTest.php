<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateUserPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_uploads_a_photo(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.photo.update'), [
            'photo' => UploadedFile::fake()->image('dwight.jpg', 400, 400),
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue($user->hasPhoto());

        foreach (User::photoPixelSizes() as $pixels) {
            Storage::disk('local')->assertExists($user->photoVariantPath($pixels));
        }
    }

    #[Test]
    public function it_removes_a_photo(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('pam.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())->delete(route('settings.photo.destroy'));

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status');

        $this->assertFalse($user->refresh()->hasPhoto());
    }

    #[Test]
    public function it_refuses_an_upload_but_still_removes_a_photo_while_file_upload_is_off(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('pam.jpg', 400, 400),
        )->execute();

        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->actingAs($user->fresh())->post(route('settings.photo.update'), [
            'photo' => UploadedFile::fake()->image('dwight.jpg', 400, 400),
        ])->assertNotFound();

        $this->actingAs($user->fresh())->delete(route('settings.photo.destroy'))
            ->assertRedirect(route('settings.profile.index'));

        $this->assertFalse($user->refresh()->hasPhoto());
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_image(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.photo.update'), [
            'photo' => UploadedFile::fake()->create('beets.pdf', 10, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('photo');
    }

    #[Test]
    public function it_refuses_an_image_wider_than_the_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.photo.update'), [
            'photo' => UploadedFile::fake()->image('beets.png', 8193, 1),
        ]);

        $response->assertSessionHasErrors('photo');
    }

    #[Test]
    public function it_serves_a_version_of_the_photo(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('jim.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())
            ->get(route('settings.photo.show', ['user' => $user, 'size' => 192]));

        $response->assertOk();
        $this->assertStringContainsString('image/webp', (string) $response->headers->get('content-type'));
    }

    #[Test]
    public function it_does_not_serve_the_photo_of_somebody_in_another_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('holly.jpg', 400, 400),
        )->execute();

        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)
            ->get(route('settings.photo.show', ['user' => $user, 'size' => 96]));

        $response->assertNotFound();
    }

    #[Test]
    public function it_serves_only_the_sizes_it_writes_to_disk(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('toby.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user->fresh())
            ->get(route('settings.photo.show', ['user' => $user, 'size' => 999]));

        $response->assertNotFound();
    }
}
