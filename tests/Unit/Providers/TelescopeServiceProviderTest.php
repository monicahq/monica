<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelescopeServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_an_administrator_view_telescope(): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->assertTrue(Gate::forUser($michael)->allows('viewTelescope'));
    }

    #[Test]
    public function it_refuses_an_administrator_who_can_no_longer_sign_in(): void
    {
        $michael = User::factory()->inactive()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->assertFalse(Gate::forUser($michael)->allows('viewTelescope'));
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_administer_the_instance(): void
    {
        $dwight = User::factory()->create();

        $this->assertFalse(Gate::forUser($dwight)->allows('viewTelescope'));
    }
}
