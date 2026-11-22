<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactLifeEventCandidateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_the_other_people_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim', 'Halpert');
        $this->contactOn($vault, 'Pam', 'Beesly');
        $this->contactOn($vault, 'Pamela', 'Morgan');
        $this->contactOn(Vault::factory()->create(['account_id' => $user->account_id]), 'Pam', 'Stamford');

        $this->actingAs($user)
            ->get(route('contacts.lifeEventCandidates.index', ['vault' => $vault, 'contact' => $jim, 'query' => 'pam']))
            ->assertOk()
            ->assertSee('Pam Beesly')
            ->assertSee('Pamela Morgan')
            ->assertDontSee('Pam Stamford')
            ->assertDontSee('Jim Halpert');
    }

    #[Test]
    public function it_leaves_out_the_people_already_chosen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim', 'Halpert');
        $pam = $this->contactOn($vault, 'Pam', 'Beesly');

        $this->actingAs($user)
            ->get(route('contacts.lifeEventCandidates.index', ['vault' => $vault, 'contact' => $jim, 'except' => [$pam->id]]))
            ->assertOk()
            ->assertDontSee('Pam Beesly');
    }

    #[Test]
    public function it_lists_the_people_in_the_order_of_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $this->contactOn($vault, 'Dwight', 'Schrute');
        $this->contactOn($vault, 'Erin', 'Élan');

        $this->actingAs($user)
            ->get(route('contacts.lifeEventCandidates.index', ['vault' => $vault, 'contact' => $michael]))
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
