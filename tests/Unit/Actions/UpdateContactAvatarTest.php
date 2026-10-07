<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactAvatar;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactAvatarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_picture_on_the_contact_and_logs_the_action(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('michael.jpg', 900, 400),
        )->execute();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertTrue($contact->hasAvatar());

        $file = $contact->avatar;

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($vault->id, $file->vault_id);
        $this->assertEquals('image/webp', $file->mime_type);
        $this->assertEquals('michael.webp', $file->original_name);
        $this->assertEquals(Contact::AVATAR_PIXELS, $file->width);
        $this->assertEquals(Contact::AVATAR_PIXELS, $file->height);
        $this->assertEquals(1, $file->reference_count);
        $this->assertNull($file->orphaned_at);

        Storage::disk('local')->assertExists($file->path);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactAvatarUpdate
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Michael Scott'],
        );
    }

    #[Test]
    public function it_writes_a_thumbnail_beside_the_picture(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('creed.jpg', 900, 400),
        )->execute();

        $file = $contact->avatar;

        $this->assertNotNull($file);
        $this->assertEquals([['suffix' => '_96', 'pixels' => 96]], $file->refresh()->variants);

        $thumbnail = $file->variantPath(Contact::AVATAR_THUMBNAIL_PIXELS);

        $this->assertNotNull($thumbnail);
        Storage::disk('local')->assertExists($thumbnail);
        $this->assertEquals([96, 96], array_slice((array) getimagesize(Storage::disk('local')->path($thumbnail)), 0, 2));
    }

    #[Test]
    public function it_charges_the_account_for_what_is_kept_and_leaves_no_scratch_file(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('pam.jpg', 900, 900),
        )->execute();

        $file = $contact->avatar;

        $this->assertNotNull($file);
        $this->assertEquals($file->size_in_bytes, Account::query()->findOrFail($user->account_id)->storage_used_in_bytes);
        $this->assertEmpty(Storage::disk('local')->files('tmp/contact-avatars'));
    }

    #[Test]
    public function it_lets_the_earlier_picture_go_without_deleting_it(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('jim.jpg', 400, 400),
        )->execute();

        $first = $contact->avatar;

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact->refresh(),
            upload: UploadedFile::fake()->image('jim-again.jpg', 400, 400),
        )->execute();

        $this->assertNotNull($first);
        $this->assertNotEquals($first->id, $contact->avatar_file_id);

        $first->refresh();

        $this->assertEquals(0, $first->reference_count);
        $this->assertNotNull($first->orphaned_at);

        Storage::disk('local')->assertExists($first->path);
    }

    #[Test]
    public function it_refuses_a_contact_kept_in_another_vault(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $other->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('dwight.jpg', 400, 400),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('angela.jpg', 400, 400),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_an_image(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->create('dundies.pdf', 4, 'application/pdf'),
        )->execute();
    }

    #[Test]
    public function it_refuses_an_image_over_five_megabytes(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('kevin.jpg')->size(6 * 1024),
        )->execute();
    }
}
