<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\WeatherHelper;
use App\Jobs\GetGPSCoordinate;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use App\Jobs\GetWeatherInformation;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WeatherHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_null_if_address_is_not_set()
    {
        $contact = factory(Contact::class)->create([]);
        $this->assertNull(WeatherHelper::getWeatherForAddress($contact->addresses()->first()));
    }

    /** @test */
    public function it_dispatch_batch_with_get_coordinates()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);
        config(['monica.enable_weather' => true]);
        config(['monica.weatherapi_key' => 'test']);

        $fake = Bus::fake();

        $address = factory(Address::class)->create();

        WeatherHelper::getWeatherForAddress($address);

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(2, $pendingBatch->jobs);
            $this->assertInstanceOf(GetGPSCoordinate::class, $pendingBatch->jobs[0]);
            $this->assertInstanceOf(GetWeatherInformation::class, $pendingBatch->jobs[1]);

            return true;
        });
    }
}
