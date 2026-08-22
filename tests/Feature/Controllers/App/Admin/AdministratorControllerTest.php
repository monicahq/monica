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
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();
        $michael = $this->administrator();

        $this->actingAs($jim)->get(route('admin.administrators.index'))->assertNotFound();
        $this->actingAs($jim)->post(route('admin.administrators.create'), ['email' => $jim->email])->assertNotFound();
        $this->actingAs($jim)->delete(route('admin.administrators.destroy', ['user' => $michael->id]))->assertNotFound();

        $this->assertDatabaseCount('administrators', 1);
    }

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
    public function it_warns_before_a_grant_and_before_a_withdrawal(): void
    {
        $michael = $this->administrator('Michael', 'Scott');
        $this->administrator('Dwight', 'Schrute');

        $response = $this->actingAs($michael)->get(route('admin.administrators.index'));

        $response->assertSee('Give somebody the admin space?', escape: false);
        $response->assertSee('Take the admin space away from Dwight Schrute?', escape: false);
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
    public function it_refuses_to_grant_a_capacity_already_held(): void
    {
        $michael = $this->administrator();
        $dwight = $this->administrator();

        $response = $this->actingAs($michael)->post(route('admin.administrators.create'), [
            'email' => $dwight->email,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('administrators', 2);
    }

    #[Test]
    public function it_closes_the_area_to_somebody_who_had_it_open(): void
    {
        $michael = $this->administrator();
        $dwight = $this->administrator();

        $this->actingAs($dwight)->get(route('admin.administrators.index'))->assertStatus(200);

        $this->actingAs($michael)->delete(route('admin.administrators.destroy', ['user' => $dwight->id]));

        $this->actingAs($dwight->refresh())->get(route('admin.administrators.index'))->assertNotFound();
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

    #[Test]
    public function it_states_the_reason_the_last_administrator_cannot_be_withdrawn(): void
    {
        $michael = $this->administrator();
        $dwight = $this->administrator();

        $this->actingAs($michael)->delete(route('admin.administrators.destroy', ['user' => $dwight->id]));

        $response = $this->actingAs($dwight->refresh())->get(route('admin.administrators.index'));
        $response->assertNotFound();

        $response = $this->actingAs($michael)->get(route('admin.administrators.index'));
        $response->assertSee('There is one administrator on this instance', escape: false);
        $response->assertDontSee('Withdraw it', escape: false);
    }

    private function administrator(string $first = 'Michael', string $last = 'Scott'): User
    {
        $user = User::factory()->create(['first_name' => $first, 'last_name' => $last]);
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
