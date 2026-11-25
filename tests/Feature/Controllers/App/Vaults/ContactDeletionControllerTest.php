<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDeletionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_before_deleting_the_selected_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = $this->contact($user, $vault, 'Toby', 'Flenderson');
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $this->actingAs($user)
            ->get(route('contacts.deletion.show', ['vault' => $vault, 'contacts' => [$toby->uuid, $todd->uuid]]))
            ->assertOk()
            ->assertSee('Delete 2 people?')
            ->assertSee(route('contacts.deletion.destroy', $vault));
    }

    #[Test]
    public function it_deletes_the_selected_people(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = $this->contact($user, $vault, 'Toby', 'Flenderson');
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $vault), ['contacts' => [$toby->uuid, $todd->uuid]])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('status', '2 people are deleted.');

        $this->assertSoftDeleted('contacts', ['id' => $toby->id]);
        $this->assertSoftDeleted('contacts', ['id' => $todd->id]);
    }

    #[Test]
    public function it_deletes_the_others_when_a_selected_person_is_already_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $creed = $this->contact($user, $vault, 'Creed', 'Bratton');
        $creed->delete();
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $vault), ['contacts' => [$creed->uuid, $todd->uuid]])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('status', '1 person is deleted.');

        $this->assertSoftDeleted('contacts', ['id' => $todd->id]);
    }

    #[Test]
    public function it_goes_back_to_the_list_when_every_selected_person_is_already_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $creed = $this->contact($user, $vault, 'Creed', 'Bratton');
        $creed->delete();

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $vault), ['contacts' => [$creed->uuid]])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionMissing('status')
            ->assertSessionMissing('error');
    }

    #[Test]
    public function it_says_why_nobody_was_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $vault), ['contacts' => [$me->uuid]])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('error', 'This is the contact that represents you, and it stays. Everything on the record is written from its point of view.');
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $uuids = array_map(fn (): string => (string) Str::uuid(), range(1, 501));

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $vault), ['contacts' => $uuids])
            ->assertSessionHasErrors('contacts');
    }

    #[Test]
    public function it_does_not_find_a_person_of_another_vault(): void
    {
        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = $this->contact($user, $stamford, 'Karen', 'Filippelli');

        $this->actingAs($user)
            ->delete(route('contacts.deletion.destroy', $scranton), ['contacts' => [$karen->uuid]])
            ->assertNotFound();

        $this->assertNotSoftDeleted('contacts', ['id' => $karen->id]);
    }

    private function contact(User $user, Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
