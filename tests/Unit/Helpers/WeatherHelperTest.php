<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\WeatherHelper;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WeatherHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_returns_null_if_address_is_not_set()
    {
        $contact = factory(Contact::class)->create([]);
        $this->assertNull(WeatherHelper::getWeatherForAddress($contact->addresses()->first()));
    }
}
