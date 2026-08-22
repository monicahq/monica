<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoSelfServiceCapacityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function signing_up_does_not_make_somebody_an_administrator(): void
    {
        $this->post(route('auth.register.create'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => '1',
        ]);

        $michael = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertDatabaseCount('administrators', 0);
        $this->assertFalse($michael->isAdministrator());
    }

    #[Test]
    public function somebody_who_just_signed_up_cannot_tell_the_admin_space_exists(): void
    {
        $this->post(route('auth.register.create'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => '1',
        ]);

        $this->get('admin')->assertNotFound();
        $this->get('admin/logs')->assertNotFound();
    }

    #[Test]
    public function editing_your_own_profile_cannot_hand_you_the_capacity(): void
    {
        $jim = User::factory()->create();

        $this->actingAs($jim)->put(route('settings.profile.update'), [
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'email' => $jim->email,
            'administrator' => '1',
            'is_administrator' => '1',
            'administrators' => '1',
        ]);

        $this->assertDatabaseCount('administrators', 0);
        $this->assertFalse($jim->refresh()->isAdministrator());
    }
}
