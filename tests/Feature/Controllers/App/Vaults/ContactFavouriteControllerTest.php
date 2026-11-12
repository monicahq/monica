<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFavouriteControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_contact_as_a_favourite(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'is_favourite' => true,
        ]);
    }

    #[Test]
    public function it_confirms_the_marking(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertSessionHas('status')
            ->assertSessionHas('status_description');
    }

    #[Test]
    public function it_clears_the_mark_on_a_favourite(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isFavourite: true);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => false,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'is_favourite' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_request_that_does_not_say_which_state_it_wants(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [])
            ->assertSessionHasErrors('favourite');

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    private function contactOf(User $user, bool $isFavourite = false): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'is_favourite' => $isFavourite,
        ]);
    }
}
