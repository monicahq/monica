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

class EmergencyContactControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_emergency_contact(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.emergencyContact.update'), [
            'name' => 'Mose Schrute',
            'phone' => '+1 570 555 0182',
            'relationship' => 'Cousin',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your emergency contact is saved.');
        $response->assertSessionHas('status_description', 'Only you and your company administrators can see this.');

        $employee->refresh();

        $this->assertEquals('Mose Schrute', $employee->emergency_contact_name);
        $this->assertEquals('+1 570 555 0182', $employee->emergency_contact_phone);
        $this->assertEquals('Cousin', $employee->emergency_contact_relationship);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->put(route('settings.emergencyContact.update'), [
            'name' => 'Mose Schrute',
        ]);

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_long(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.emergencyContact.update'), [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('name');
    }
}
