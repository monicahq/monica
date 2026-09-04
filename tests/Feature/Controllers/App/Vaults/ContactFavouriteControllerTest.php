<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
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
    public function it_leaves_a_contact_who_is_already_a_favourite_marked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isFavourite: true);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]));

        $this->assertTrue($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_shows_the_confirmation_on_the_page_the_redirect_lands_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->followingRedirects()
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertOk()
            ->assertSee('Marked as a favourite.')
            ->assertSee('They sit at the top of the list.');
    }

    #[Test]
    public function it_records_the_marking_in_the_activity(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ContactFavourited;
        });
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
            ->assertSessionHas('status', 'Marked as a favourite.')
            ->assertSessionHas('status_description', 'They sit at the top of the list.');
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
    public function it_leaves_a_contact_who_is_not_a_favourite_unmarked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => false,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]));

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_records_the_clearing_in_the_activity(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isFavourite: true);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => false,
            ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ContactUnfavourited;
        });
    }

    #[Test]
    public function it_confirms_the_removal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isFavourite: true);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => false,
            ])
            ->assertSessionHas('status', 'No longer a favourite.')
            ->assertSessionHas('status_description', 'They go back in with everybody else.');
    }

    #[Test]
    public function it_destroys_nothing_when_it_clears_the_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user, isFavourite: true);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => false,
            ]);

        $this->assertModelExists($contact);
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

    #[Test]
    public function it_refuses_a_state_that_is_not_a_boolean(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => 'sideways',
            ])
            ->assertSessionHasErrors('favourite');

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_does_not_find_a_contact_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $contact = $this->contactOf($stranger);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertNotFound()
            ->assertSessionMissing('status');

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_does_not_find_a_contact_reached_through_another_vault_of_the_same_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contacts.favourite.update', ['vault' => $elsewhere, 'contact' => $contact]), [
                'favourite' => true,
            ])
            ->assertNotFound()
            ->assertSessionMissing('status');

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_refuses_a_visitor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->put(route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]), [
            'favourite' => true,
        ])->assertRedirect(route('auth.login.new'));

        $this->assertFalse($contact->refresh()->is_favourite);
    }

    private function contactOf(User $user, bool $isFavourite = false): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => TemplateVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'is_favourite' => $isFavourite,
        ]);
    }
}
