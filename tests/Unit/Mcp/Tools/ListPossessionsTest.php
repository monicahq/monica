<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessions;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_possessions_of_the_vault_and_nothing_else(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        Possession::factory()->create(['name' => 'Wernham Hogg mug']);

        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Chrysler Sebring')
            ->assertDontSee('Wernham Hogg mug');
    }

    #[Test]
    public function it_lists_the_possessions_kept_at_a_location_or_inside_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet cart', 'current_location_id' => $annex->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Forklift']);

        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid, 'location' => $office->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Beet cart')
            ->assertDontSee('Forklift');
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $utica = Location::factory()->create(['name' => 'Utica branch']);

        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid, 'location' => $utica->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_lists_compact_possessions_with_their_current_value(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        PossessionValuation::factory()->create(['possession_id' => $sebring->id, 'amount' => '7500.00']);
        PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'value' => 'Convertible']);

        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid, 'compact' => true])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Chrysler Sebring')
            ->assertSee('7500.00')
            ->assertDontSee('Convertible');
    }

    #[Test]
    public function it_asks_the_database_as_many_times_for_five_possessions_as_for_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->onePossessionWithEverything($vault);

        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid])->assertOk();

        DB::enableQueryLog();
        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid])->assertOk();
        $forOne = count(DB::getQueryLog());

        foreach (range(1, 4) as $ignored) {
            $this->onePossessionWithEverything($vault);
        }

        DB::flushQueryLog();
        MonicaServer::actingAs($user)->tool(ListPossessions::class, ['vault' => $vault->uuid])->assertOk();

        $this->assertSame($forOne, count(DB::getQueryLog()));
    }

    private function onePossessionWithEverything(Vault $vault): void
    {
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        PossessionValuation::factory()->create(['possession_id' => $possession->id]);
        $possession->tags()->attach(PossessionTag::factory()->create(['account_id' => $vault->account_id]));
    }
}
