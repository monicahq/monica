<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Weather;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WeatherTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $weather = factory(Weather::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($weather->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_place()
    {
        $weather = factory(Weather::class)->create([]);
        $this->assertTrue($weather->place()->exists());
    }

    /** @test */
    public function it_gets_current_temperature()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            13,
            $weather->temperature()
        );
    }

    /** @test */
    public function it_gets_current_temperature_in_celsius()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            13,
            $weather->temperature('celsius')
        );
    }

    /** @test */
    public function it_gets_current_temperature_in_fahrenheit()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            55.4,
            $weather->temperature('fahrenheit')
        );
    }

    /** @test */
    public function it_gets_current_summary()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            'Partly cloudy',
            $weather->summary
        );
    }

    /** @test */
    public function it_gets_current_code()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            'partly-cloudy-night',
            $weather->summary_code
        );
    }

    /** @test */
    public function it_gets_weather_emoji()
    {
        $weather = factory(Weather::class)->create();

        $this->assertEquals(
            '🎑',
            $weather->emoji
        );
    }
}
