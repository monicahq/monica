<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\VaultColourEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLocationTreeBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_location_of_the_account_under_its_parent_after_all_locations(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        Location::factory()->inside($annex)->create(['name' => 'Toby’s desk']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Farm']);
        Location::factory()->create(['name' => 'Utica branch']);

        $nodes = new PossessionsViewModel(user: $user, vault: $vault)->tree()->nodes();

        $this->assertSame(['Farm', 'Office', 'Annex', 'Toby’s desk'], array_slice(array_column($nodes, 'name'), 1));
        $this->assertSame([0, 0, 0, 1, 2], array_column($nodes, 'depth'));
        $this->assertSame(['layers', 'place', 'place', 'chevron-right', 'chevron-right'], array_column($nodes, 'icon'));
        $this->assertSame(route('possessions.locations.show', ['vault' => $vault, 'location' => $annex]), $nodes[3]['url']);
    }

    #[Test]
    public function it_counts_the_possessions_of_this_vault_kept_at_a_location_or_inside_it(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Farm']);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $annex->id]);
        Possession::factory()->create(['vault_id' => $vault->id]);
        Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'current_location_id' => $annex->id]);

        $nodes = new PossessionsViewModel(user: $user, vault: $vault)->tree()->nodes();

        $this->assertSame([3, 0, 2, 1], array_column($nodes, 'count'));
    }

    #[Test]
    public function it_gives_every_location_the_colour_of_its_top_level_location(): void
    {
        [$user, $vault] = $this->scranton();
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Farm']);
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        Location::factory()->inside($office)->create(['name' => 'Annex']);

        $nodes = new PossessionsViewModel(user: $user, vault: $vault)->tree()->nodes();

        $this->assertSame(VaultColourEnum::Blue->value, $nodes[1]['colour']);
        $this->assertSame(VaultColourEnum::Green->value, $nodes[2]['colour']);
        $this->assertSame(VaultColourEnum::Green->value, $nodes[3]['colour']);
    }

    #[Test]
    public function it_marks_the_chosen_location_as_the_current_one(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);

        $everywhere = new PossessionsViewModel(user: $user, vault: $vault)->tree()->nodes();
        $atTheOffice = new PossessionsViewModel(user: $user, vault: $vault, location: $office)->tree()->nodes();

        $this->assertSame([true, false], array_column($everywhere, 'isCurrent'));
        $this->assertSame([false, true], array_column($atTheOffice, 'isCurrent'));
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
