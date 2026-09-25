<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_greets_somebody_who_has_just_arrived(): void
    {
        $user = User::factory()->notWelcomed()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->actingAs($user)->get(route('welcome.index'))
            ->assertStatus(200)
            ->assertSee('Hi Pam, we’re glad you’re here.')
            ->assertSee('Let me in')
            ->assertSee('action="'.route('welcome.update').'"', false);
    }

    #[Test]
    public function it_still_opens_for_somebody_who_was_welcomed_before(): void
    {
        $user = User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->actingAs($user)->get(route('welcome.index'))
            ->assertStatus(200)
            ->assertSee('Hi Jim, we’re glad you’re here.');
    }

    #[Test]
    public function it_marks_somebody_as_welcomed_and_sends_them_home(): void
    {
        $user = User::factory()->notWelcomed()->create();

        $this->actingAs($user)->put(route('welcome.update'))
            ->assertRedirect(route('home.index'));

        $this->assertNotNull($user->refresh()->welcomed_at);
    }

    #[Test]
    public function it_sends_somebody_already_welcomed_home_without_moving_the_moment(): void
    {
        $firstVisit = now()->subMonth()->startOfSecond();
        $user = User::factory()->create(['welcomed_at' => $firstVisit]);

        $this->actingAs($user)->put(route('welcome.update'))
            ->assertRedirect(route('home.index'));

        $this->assertEquals($firstVisit, $user->refresh()->welcomed_at);
    }
}
