<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Weather;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WeatherTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_current_temperature()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            7.57,
            $weather->temperature
        );
    }

    public function test_it_gets_current_summary()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            'Mostly Cloudy',
            $weather->summary
        );
    }

    public function test_it_gets_current_icon()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            'partly-cloudy-night',
            $weather->summaryIcon
        );
    }
}
