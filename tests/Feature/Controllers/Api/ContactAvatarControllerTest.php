<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactAvatarControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_picture_on_a_contact(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.avatar.id', $contact->refresh()->avatar->uuid);
        $this->assertNotNull($contact->refresh()->avatar_file_id);
        $this->assertDatabaseHas('files', ['id' => $contact->refresh()->avatar_file_id, 'vault_id' => $vault->id]);
    }

    #[Test]
    public function it_refuses_a_file_that_is_not_a_picture(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->create('dundies.pdf', 10, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['avatar']);
        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_refuses_a_picture_wider_than_the_limit(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('dundies.png', 8193, 1),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['avatar']);
        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_refuses_a_picture_that_would_take_the_account_past_its_storage(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update([
            'storage_limit_in_bytes' => 1024,
            'storage_used_in_bytes' => 1024,
        ]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['avatar']);
        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_takes_a_picture_off_and_draws_the_initials_again(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $colour = $contact->avatar_colour;

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ])->assertOk();

        $response = $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.avatar', null);
        $response->assertJsonPath('data.attributes.avatar_colour', $colour);
        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_refuses_an_upload_but_still_takes_the_picture_off_while_file_upload_is_off(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ])->assertOk();

        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('jim.jpg', 400, 400),
        ])->assertNotFound();

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar")->assertOk();

        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ])->assertNotFound();
        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar")->assertNotFound();
        $this->assertNull($contact->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$other->uuid}/contacts/{$contact->uuid}/avatar", [
            'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
        ])->assertNotFound();
        $this->assertNull($contact->refresh()->avatar_file_id);
    }
}
