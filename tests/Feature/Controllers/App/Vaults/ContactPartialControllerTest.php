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

class ContactPartialControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_a_full_contact_partial(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isPartial: false);

        $this->actingAs($user)
            ->put(route('contacts.partial.update', ['vault' => $contact->vault, 'contact' => $contact]), ['partial' => true])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHas('status', 'Holly Flax is a partial contact.');

        $this->assertTrue($contact->refresh()->is_partial);
    }

    #[Test]
    public function it_makes_a_partial_contact_a_full_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isPartial: true);

        $this->actingAs($user)
            ->put(route('contacts.partial.update', ['vault' => $contact->vault, 'contact' => $contact]), ['partial' => false])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHas('status', 'Holly Flax is a full contact.');

        $this->assertFalse($contact->refresh()->is_partial);
    }

    #[Test]
    public function it_refuses_a_request_that_does_not_say_which_state_it_wants(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isPartial: false);

        $this->actingAs($user)
            ->put(route('contacts.partial.update', ['vault' => $contact->vault, 'contact' => $contact]), [])
            ->assertSessionHasErrors('partial');

        $this->assertFalse($contact->refresh()->is_partial);
    }

    #[Test]
    public function it_shows_the_refusal_for_the_contact_representing_the_account_holder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isPartial: false, isMe: true);

        $this->actingAs($user)
            ->put(route('contacts.partial.update', ['vault' => $contact->vault, 'contact' => $contact]), ['partial' => true])
            ->assertSessionHasErrors('partial');

        $this->assertFalse($contact->refresh()->is_partial);
    }

    #[Test]
    public function it_answers_not_found_through_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isPartial: true);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contacts.partial.update', ['vault' => $otherVault, 'contact' => $contact]), ['partial' => false])
            ->assertNotFound();

        $this->assertTrue($contact->refresh()->is_partial);
    }

    private function contactOf(User $user, bool $isPartial, bool $isMe = false): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Holly',
            'last_name' => 'Flax',
            'is_partial' => $isPartial,
            'is_me' => $isMe,
        ]);
    }
}
