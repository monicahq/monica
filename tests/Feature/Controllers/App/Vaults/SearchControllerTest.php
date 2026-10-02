<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_search_page(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('search.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Search for');
        $response->assertSee('role="search"', escape: false);
        $response->assertSee('Nothing searched yet');
    }

    #[Test]
    public function it_asks_for_at_least_two_letters(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $vault, 'query' => 'd']))
            ->assertSee('Type at least two letters or digits to search.');
    }

    #[Test]
    public function it_says_search_is_unavailable_until_an_engine_is_configured(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $vault, 'query' => 'schrute']))
            ->assertStatus(200)
            ->assertSee('Search is unavailable right now');
    }

    #[Test]
    public function it_refuses_a_scope_it_does_not_know(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $vault, 'query' => 'schrute', 'scope' => 'galaxy']))
            ->assertSessionHasErrors('scope');
    }

    #[Test]
    public function it_lists_what_was_found_and_where_when_searching_every_vault(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $office = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $farm = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $farm->id, 'first_name' => 'Mose', 'last_name' => 'Schrute']);
        $this->waitForSearchEngine();

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $office, 'query' => 'schrute']))
            ->assertDontSeeText('Mose Schrute');

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $office, 'query' => 'schrute', 'scope' => 'account']))
            ->assertSeeText('Mose Schrute')
            ->assertSee('1 result for “schrute” in every vault', escape: false)
            ->assertSee('x-data="{ shown: \'all\' }"', escape: false)
            ->assertSee('Schrute Farms');
    }
}
