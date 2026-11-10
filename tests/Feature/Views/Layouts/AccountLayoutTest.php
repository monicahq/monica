<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_full_access_to_a_free_account_on_the_vault_list(): void
    {
        $user = User::factory()->create(['first_name' => 'Stanley']);

        $this->actingAs($user)
            ->get(route('vaults.index'))
            ->assertOk()
            ->assertSee('Subscribe now to lift the limits of the free plan!', escape: false);
    }

    #[Test]
    public function the_name_of_the_application_in_the_bar_leads_to_the_vaults(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('href="'.route('vaults.index').'"', escape: false);
    }
}
