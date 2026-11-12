<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SupportAvailability;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_support_once_somebody_administers_the_instance(): void
    {
        $this->assertFalse(SupportAvailability::isOffered());

        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertTrue(SupportAvailability::isOffered());
    }

    #[Test]
    public function it_offers_nothing_while_support_is_switched_off(): void
    {
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);

        config(['monica.support_enabled' => false]);

        $this->assertFalse(SupportAvailability::isEnabled());
        $this->assertFalse(SupportAvailability::isOffered());
    }
}
