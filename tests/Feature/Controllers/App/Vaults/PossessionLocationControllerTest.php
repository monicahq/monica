<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Location;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_is_kept_at_a_location(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Paper shredder', 'current_location_id' => $office->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet cart']);

        $this->actingAs($user)->get(route('possessions.locations.show', ['vault' => $vault, 'location' => $office]))
            ->assertStatus(200)
            ->assertSee('Paper shredder')
            ->assertDontSee('Beet cart')
            ->assertSeeInOrder(['aria-label="Locations"', 'aria-current="page"', 'Office'], escape: false);
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
