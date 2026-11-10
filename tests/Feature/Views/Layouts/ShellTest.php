<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShellTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_full_access_to_a_free_account_inside_a_vault(): void
    {
        $user = User::factory()->create(['first_name' => 'Kevin']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('vaults.show', $vault))
            ->assertOk()
            ->assertSee('aria-label="Free plan usage"', escape: false);
    }

    #[Test]
    public function it_leaves_the_offer_out_of_billing_and_the_screens_reached_from_it(): void
    {
        $user = User::factory()->create(['first_name' => 'Oscar']);

        $this->actingAs($user)
            ->get(route('settings.billing.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Free plan usage"', escape: false);

        $this->actingAs($user)
            ->get(route('settings.invoices.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Free plan usage"', escape: false);
    }
}
