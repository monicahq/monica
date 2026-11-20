<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_timeline_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $costume = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Holiday Armadillo costume']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'title' => 'Dry cleaned before storage']);

        $this->actingAs($user)->get(route('possessions.timeline.index', [$vault, $costume, 'maintenance']))
            ->assertOk()
            ->assertSee('How the timeline works');
    }

    #[Test]
    public function it_answers_not_found_for_a_section_the_timeline_does_not_have(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $costume = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->get(route('possessions.timeline.index', [$vault, $costume, 'documents']))->assertNotFound();
    }
}
