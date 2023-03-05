<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DistanceHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DistanceHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_distance_according_to_the_user_preference(): void
    {
        $distance = 134;
        $unit = User::DISTANCE_UNIT_KM;
        $user = User::factory()->create([
            'distance_format' => User::DISTANCE_UNIT_KM,
        ]);
        $this->assertEquals(
            '134 km',
            DistanceHelper::format($user, $distance, $unit)
        );

        $distance = 134;
        $unit = User::DISTANCE_UNIT_MILES;
        $user = User::factory()->create([
            'distance_format' => User::DISTANCE_UNIT_KM,
        ]);
        $this->assertEquals(
            '215.65 km',
            DistanceHelper::format($user, $distance, $unit)
        );

        $distance = 134;
        $unit = User::DISTANCE_UNIT_MILES;
        $user = User::factory()->create([
            'distance_format' => User::DISTANCE_UNIT_MILES,
        ]);
        $this->assertEquals(
            '134 miles',
            DistanceHelper::format($user, $distance, $unit)
        );

        $distance = 134;
        $unit = User::DISTANCE_UNIT_KM;
        $user = User::factory()->create([
            'distance_format' => User::DISTANCE_UNIT_MILES,
        ]);
        $this->assertEquals(
            '83.26 miles',
            DistanceHelper::format($user, $distance, $unit)
        );
    }
}
