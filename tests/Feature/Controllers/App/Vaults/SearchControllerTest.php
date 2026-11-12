<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

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
        $response->assertSee('role="search"', escape: false);
    }

    #[Test]
    public function it_says_search_is_unavailable_until_an_engine_is_configured(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('search.index', ['vault' => $vault, 'query' => 'schrute']))
            ->assertStatus(200);
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
}
