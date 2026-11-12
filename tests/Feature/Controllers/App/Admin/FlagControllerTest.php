<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlagControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_flags_and_says_when_none_is_declared(): void
    {
        Feature::forgetDrivers();
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->get(route('admin.flags.index'))
            ->assertOk();
    }
}
