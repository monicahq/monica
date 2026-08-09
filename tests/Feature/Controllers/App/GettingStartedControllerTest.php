<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GettingStartedControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_greets_somebody_who_has_not_made_anything_yet(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->actingAs($user)->get(route('gettingStarted.index'))
            ->assertStatus(200)
            ->assertSee('Hello Michael')
            ->assertSee('Regis Freyd')
            ->assertSee('Create your first vault')
            ->assertSee('Getting set up')
            ->assertSee('0 of 5 done');
    }

    #[Test]
    public function it_ticks_off_what_the_account_has_already_been_through(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('gettingStarted.index'))
            ->assertStatus(200)
            ->assertSee('1 of 5 done');
    }

    #[Test]
    public function it_keeps_somebody_who_is_not_signed_in_out(): void
    {
        $this->get(route('gettingStarted.index'))->assertRedirect(route('auth.login.new'));
    }
}
