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
            ->assertSee('action="'.route('welcome.update').'"', false);
    }

    #[Test]
    public function it_still_opens_for_somebody_who_was_welcomed_before(): void
    {
        $user = User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->actingAs($user)->get(route('welcome.index'))
            ->assertStatus(200);
    }

    #[Test]
    public function it_marks_somebody_as_welcomed_and_sends_them_home(): void
    {
        $user = User::factory()->notWelcomed()->create();

        $this->actingAs($user)->put(route('welcome.update'))
            ->assertRedirect(route('home.index'));

        $this->assertNotNull($user->refresh()->welcomed_at);
    }
}
