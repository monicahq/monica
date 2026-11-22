<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultLifeEventCandidateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_everybody_in_the_vault_the_signed_in_person_included(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->contactOn($vault, 'Michael', 'Scott')->update(['is_me' => true]);
        $this->contactOn($vault, 'Pam', 'Beesly');
        $this->contactOn(Vault::factory()->create(['account_id' => $user->account_id]), 'Karen', 'Filippelli');

        $this->actingAs($user)
            ->get(route('vaults.lifeEventCandidates.index', $vault))
            ->assertOk()
            ->assertSee('Michael Scott')
            ->assertSee('Pam Beesly')
            ->assertDontSee('Karen Filippelli');
    }

    #[Test]
    public function it_narrows_to_what_was_typed_and_leaves_out_the_people_already_chosen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = $this->contactOn($vault, 'Pam', 'Beesly');
        $this->contactOn($vault, 'Pamela', 'Morgan');
        $this->contactOn($vault, 'Jim', 'Halpert');

        $this->actingAs($user)
            ->get(route('vaults.lifeEventCandidates.index', ['vault' => $vault, 'query' => 'pam', 'except' => [$pam->id]]))
            ->assertOk()
            ->assertSee('Pamela Morgan')
            ->assertDontSee('Pam Beesly')
            ->assertDontSee('Jim Halpert');
    }

    #[Test]
    public function it_fills_the_list_of_who_it_happened_to_when_asked(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('vaults.lifeEventCandidates.index', ['vault' => $vault, 'list' => 'life-event-subjects']))
            ->assertOk()
            ->assertSee('id="life-event-subjects"', escape: false);

        $this->actingAs($user)
            ->get(route('vaults.lifeEventCandidates.index', ['vault' => $vault, 'list' => 'contact-facts']))
            ->assertSessionHasErrors('list');
    }

    #[Test]
    public function it_lists_the_people_in_the_order_of_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->contactOn($vault, 'Dwight', 'Schrute');
        $this->contactOn($vault, 'Erin', 'Élan');

        $this->actingAs($user)
            ->get(route('vaults.lifeEventCandidates.index', $vault))
            ->assertOk()
            ->assertSeeInOrder(['Erin Élan', 'Dwight Schrute']);
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
