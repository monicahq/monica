<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\UpdateContactAvatar;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactAvatarControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_picture_on_a_contact(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $response = $this->actingAs($user)->post(
            route('contacts.avatar.update', ['vault' => $vault, 'contact' => $contact]),
            ['avatar' => UploadedFile::fake()->image('michael.jpg', 400, 400)],
        );

        $response->assertRedirect(route('contacts.edit', ['vault' => $vault, 'contact' => $contact]));
        $response->assertSessionHas('status', 'The photo is saved.');

        $contact->refresh();

        $this->assertTrue($contact->hasAvatar());

        Storage::disk('local')->assertExists((string) $contact->avatar?->path);
    }

    #[Test]
    public function it_takes_the_picture_off_a_contact(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        new UpdateContactAvatar(
            user: $user,
            vault: $vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('pam.jpg', 400, 400),
        )->execute();

        $response = $this->actingAs($user)->delete(
            route('contacts.avatar.destroy', ['vault' => $vault, 'contact' => $contact]),
        );

        $response->assertRedirect(route('contacts.edit', ['vault' => $vault, 'contact' => $contact]));
        $response->assertSessionHas('status', 'The photo is removed.');

        $this->assertFalse($contact->refresh()->hasAvatar());
    }

    #[Test]
    public function it_cannot_reach_a_contact_kept_in_another_vault(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $other->id]);

        $response = $this->actingAs($user)->post(
            route('contacts.avatar.update', ['vault' => $vault, 'contact' => $contact]),
            ['avatar' => UploadedFile::fake()->image('jim.jpg', 400, 400)],
        );

        $response->assertNotFound();

        $this->assertFalse($contact->refresh()->hasAvatar());
    }

    #[Test]
    public function it_refuses_something_that_is_not_an_image(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $response = $this->actingAs($user)->post(
            route('contacts.avatar.update', ['vault' => $vault, 'contact' => $contact]),
            ['avatar' => UploadedFile::fake()->create('dundies.pdf', 10, 'application/pdf')],
        );

        $response->assertInvalid('avatar');

        $this->assertFalse($contact->refresh()->hasAvatar());
    }

    #[Test]
    public function it_refuses_a_picture_that_would_take_the_account_past_its_storage(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update([
            'storage_limit_in_bytes' => 1024,
            'storage_used_in_bytes' => 1024,
        ]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(
            route('contacts.avatar.update', ['vault' => $vault, 'contact' => $contact]),
            ['avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400)],
        );

        $response->assertInvalid('avatar');

        $this->assertFalse($contact->refresh()->hasAvatar());
    }
}
