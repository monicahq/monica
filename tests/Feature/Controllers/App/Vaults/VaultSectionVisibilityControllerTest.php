<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultSectionVisibilityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_every_section_whose_switch_is_off(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'colour' => '#aa151b',
        ]);

        $response = $this->actingAs($user)->put(route('vaults.sectionVisibility.update', $vault), [
            'shows_people' => '1',
        ]);

        $response->assertRedirect(route('vaults.settings.index', $vault));
        $response->assertSessionHas('status', 'The vault is saved.');
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Dunder Mifflin',
            'colour' => '#aa151b',
            'shows_people' => true,
            'shows_things' => false,
            'shows_knowledge' => false,
        ]);
    }

    #[Test]
    public function it_refuses_a_value_that_is_not_true_or_false(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->put(route('vaults.sectionVisibility.update', $vault), [
            'shows_people' => 'maybe',
        ]);

        $response->assertSessionHasErrors('shows_people');
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'shows_people' => true]);
    }
}
