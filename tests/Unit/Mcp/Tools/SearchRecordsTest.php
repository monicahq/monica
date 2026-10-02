<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SearchRecords;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchRecordsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_what_the_search_found(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $this->waitForSearchEngine();

        EricaServer::actingAs($user)->tool(SearchRecords::class, ['vault' => $vault->uuid, 'query' => 'Scrute'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Dwight Schrute');
    }

    #[Test]
    public function it_says_search_is_unavailable_without_an_engine(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SearchRecords::class, ['vault' => $vault->uuid, 'query' => 'Schrute'])
            ->assertHasErrors(['Search is unavailable right now']);
    }
}
