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

class ContactNameControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_somebody_is_called(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'names' => [['id' => $contact->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scarn']],
                'display' => 0,
                'nickname' => 'Prison Mike',
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact->fresh()]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_name' => 'Scarn',
            'nickname' => 'Prison Mike',
            'sort_name' => 'scarn michael',
        ]);
    }

    private function contactOf(User $user): Contact
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
        ]);
    }

    #[Test]
    public function a_rename_takes_the_reader_to_the_rebuilt_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $before = $contact->slug;

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'names' => [['kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scarn']],
                'display' => 0,
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact->fresh()]));

        $this->assertSame('michael-scarn', $contact->fresh()->slug);
        $this->assertNotSame($before, $contact->fresh()->slug);
    }

    #[Test]
    public function it_refuses_a_pronunciation_with_no_system_on_its_row(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'names' => [['kind' => 'phonetic', 'formatted_name' => 'Maikeru']],
                'display' => 0,
            ])
            ->assertSessionHasErrors('names.0.phonetic_system');
    }

    #[Test]
    public function it_refuses_a_way_of_writing_that_belongs_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $dwight = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'names' => [['id' => $dwight->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Dwight']],
                'display' => 0,
            ])
            ->assertSessionHasErrors('names.0.id');
    }

    #[Test]
    public function it_leaves_somebody_nameless_when_the_editor_sends_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), ['names_included' => '1', 'nickname' => 'Mike'])
            ->assertRedirect();

        $this->assertSame('Mike', $contact->refresh()->name);
        $this->assertNull($contact->display_name_representation_id);
    }

    #[Test]
    public function it_keeps_the_names_when_the_form_arrives_without_the_editor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $shown = $contact->displayNameRepresentation;

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), ['display' => '', 'nickname' => 'Prison Mike'])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact]));

        $contact->refresh();

        $this->assertSame('Prison Mike', $contact->nickname);
        $this->assertSame($shown->id, $contact->display_name_representation_id);
        $this->assertSame('Michael Scott', $contact->name);
        $this->assertSame(1, $contact->nameRepresentations()->count());
    }

    #[Test]
    public function it_refuses_a_pronunciation_as_the_name_shown_on_the_form(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->from(route('contacts.edit', ['vault' => $contact->vault, 'contact' => $contact]))
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'names' => [
                    ['id' => $contact->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scott'],
                    ['kind' => 'phonetic', 'phonetic_system' => 'x-romaji', 'given_name' => 'Maikeru'],
                ],
                'display' => 1,
            ])
            ->assertRedirect(route('contacts.edit', ['vault' => $contact->vault, 'contact' => $contact]))
            ->assertSessionHasErrors(['names' => __('A pronunciation cannot be the name the contact is shown under.')]);

        $this->assertSame(1, $contact->nameRepresentations()->count());
    }
}
