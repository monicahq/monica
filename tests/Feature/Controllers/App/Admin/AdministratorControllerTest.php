<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_everybody_who_runs_the_instance(): void
    {
        $michael = $this->administrator('Michael', 'Scott');
        $dwight = $this->administrator('Dwight', 'Schrute');

        $response = $this->actingAs($michael)->get(route('admin.administrators.index'));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('Dwight Schrute', escape: false);
        $response->assertSee('This is you', escape: false);
        $response->assertSee($dwight->email, escape: false);
    }

    #[Test]
    public function it_grants_the_capacity_by_address(): void
    {
        $michael = $this->administrator();
        $dwight = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $response = $this->actingAs($michael)->post(route('admin.administrators.create'), [
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $response->assertRedirect(route('admin.administrators.index'));
        $response->assertSessionHas('status', 'Dwight Schrute administers this instance.');

        $this->assertTrue($dwight->refresh()->isAdministrator());
        $this->actingAs($dwight->refresh())->get(route('admin.administrators.index'))->assertStatus(200);
        $this->assertDatabaseHas('administrator_logs', ['action' => 'administrator_access_granted']);
    }

    #[Test]
    public function it_says_no_such_person_exists(): void
    {
        $response = $this->actingAs($this->administrator())->post(route('admin.administrators.create'), [
            'email' => 'nobody@dundermifflin.com',
        ]);

        $response->assertSessionHasErrors(['email' => 'Nobody on this instance uses that address.']);
        $this->assertDatabaseCount('administrators', 1);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_withdraw_their_own(): void
    {
        $michael = $this->administrator();
        $this->administrator();

        $response = $this->actingAs($michael)->delete(route('admin.administrators.destroy', ['user' => $michael->id]));

        $response->assertSessionHasErrors('administrator');
        $this->assertTrue($michael->refresh()->isAdministrator());
    }

    private function administrator(string $first = 'Michael', string $last = 'Scott'): User
    {
        $user = User::factory()->create(['first_name' => $first, 'last_name' => $last]);
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
