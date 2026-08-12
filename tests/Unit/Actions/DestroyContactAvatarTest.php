<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContactAvatar;
use App\Actions\UpdateContactAvatar;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyContactAvatarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_picture_off_the_contact_and_logs_the_action(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $contact = new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('dwight.jpg', 400, 400),
        )->execute();

        $file = $contact->avatar;

        $contact = new DestroyContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact->refresh(),
        )->execute();

        $this->assertFalse($contact->hasAvatar());

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_file_id' => null,
        ]);

        $this->assertNotNull($file);

        $file->refresh();

        $this->assertEquals(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);

        // The sweep is the only thing that takes bytes off the disk.
        Storage::disk('local')->assertExists($file->path);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactAvatarDeletion
                && $job->parameters === ['name' => 'Dwight Schrute'],
        );
    }

    #[Test]
    public function it_does_nothing_to_a_contact_drawn_as_their_initials(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contact = new DestroyContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
        )->execute();

        $this->assertFalse($contact->hasAvatar());

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_refuses_a_contact_kept_in_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $other->id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
        )->execute();
    }
}
