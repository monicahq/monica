<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\NameOrderEnum;
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
                'first_name' => 'Michael',
                'last_name' => 'Scarn',
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

    #[Test]
    public function it_keeps_the_order_a_name_reads_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'first_name' => 'Michael',
                'last_name' => 'Scott',
                'name_order' => NameOrderEnum::FamilyFirst->value,
            ]);

        $this->assertEquals(NameOrderEnum::FamilyFirst, $contact->refresh()->name_order);
    }

    #[Test]
    public function it_refuses_a_name_order_it_does_not_know(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'first_name' => 'Michael',
                'name_order' => 'sideways',
            ])
            ->assertSessionHasErrors('name_order');
    }

    #[Test]
    public function it_refuses_somebody_in_another_vault(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('contacts.name.update', ['vault' => $elsewhere, 'contact' => $contact]), [
                'first_name' => 'Michael',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_somebody_in_another_account(): void
    {
        $contact = $this->contactOf(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
                'first_name' => 'Somebody else',
            ])
            ->assertNotFound();
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
                'first_name' => 'Michael',
                'last_name' => 'Scarn',
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact->fresh()]));

        $this->assertSame('michael-scarn', $contact->fresh()->slug);
        $this->assertNotSame($before, $contact->fresh()->slug);
    }

    #[Test]
    public function the_address_somebody_was_read_at_before_a_rename_stops_answering(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $before = $contact->slug;

        $this->actingAs($user)->put(route('contacts.name.update', ['vault' => $contact->vault, 'contact' => $contact]), [
            'first_name' => 'Michael',
            'last_name' => 'Scarn',
        ]);

        $this->actingAs($user)
            ->get(url("/vaults/{$contact->vault->slug}/contacts/{$before}"))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(url("/vaults/{$contact->vault->slug}/contacts/{$contact->uuid}"))
            ->assertStatus(200);
    }
}
