<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_somebody_who_is_not_signed_in_to_the_sign_in_screen(): void
    {
        $this->get(route('home.index'))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_sends_an_account_that_holds_nothing_to_the_screen_that_says_what_to_do_first(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home.index'))
            ->assertRedirect(route('gettingStarted.index'));
    }

    #[Test]
    public function it_sends_an_account_that_holds_a_vault_to_its_vaults(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('home.index'))
            ->assertRedirect(route('vaults.index'));
    }

    #[Test]
    public function it_counts_only_the_vaults_of_the_account_it_is_asked_about(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create();

        $this->actingAs($user)->get(route('home.index'))
            ->assertRedirect(route('gettingStarted.index'));
    }
}
