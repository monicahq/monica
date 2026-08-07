<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_profile_screen(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('Pam', escape: false);
        $response->assertSee('Beesly', escape: false);
        $response->assertSee('Dunder Mifflin', escape: false);
    }

    #[Test]
    public function it_shows_the_toast_of_the_previous_save(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'status' => 'Your details are saved.',
                'status_description' => 'Your colleagues see them right away.',
            ])
            ->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('id="notifications"', escape: false);
        $response->assertSee('Your details are saved.', escape: false);
        $response->assertSee('Your colleagues see them right away.', escape: false);
    }

    #[Test]
    public function it_points_to_the_logs_screen(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.logs.index'), escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.profile.index'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_updates_the_details(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => 'The Boss',
            'work_email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your details are saved.');
        $response->assertSessionHas('status_description', 'Your colleagues see them right away.');

        $employee->refresh();

        $this->assertEquals('Michael', $employee->first_name);
        $this->assertEquals('Scott', $employee->last_name);
        $this->assertEquals('The Boss', $employee->display_name);
        $this->assertEquals('michael.scott@dundermifflin.com', $employee->work_email);
    }

    #[Test]
    public function it_refuses_details_without_a_name(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => '',
            'last_name' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function it_refuses_a_work_email_that_is_not_an_email(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Angela',
            'last_name' => 'Martin',
            'work_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('work_email');
    }
}
