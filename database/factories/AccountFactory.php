<?php

use Illuminate\Support\Str;
use function Safe\json_decode;

$factory->define(App\Models\Account\Account::class, function (Faker\Generator $faker) {
    return [
        'api_key' => Str::random(30),
    ];
});

$factory->define(App\Models\Account\Activity::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'activity_type_id' => function (array $data) {
            return factory(App\Models\Account\ActivityType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'description' => $faker->sentence,
        'summary' => $faker->sentence,
        'happened_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Account\ActivityType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'activity_type_category_id' => function (array $data) {
            return factory(App\Models\Account\ActivityTypeCategory::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'translation_key' => $faker->sentence,
        'location_type' => $faker->word,
    ];
});

$factory->define(App\Models\Account\ActivityTypeCategory::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'translation_key' => $faker->sentence,
        'name' => $faker->sentence,
    ];
});

$factory->define(App\Models\Account\Company::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => 'Central Perk',
        'website' => 'https://centralperk.com',
        'number_of_employees' => 4,
    ];
});

$factory->define(App\Models\Account\ImportJob::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'user_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Account\ImportJobReport::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\Account\Invitation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'invited_by_user_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'invitation_key' => Str::random(100),
    ];
});

$factory->define(App\Models\Account\Photo::class, function (Faker\Generator $faker) {
    $account = factory(App\Models\Account\Account::class)->create();

    return [
        'account_id' => $account->id,
        'original_filename' => 'file.jpg',
        'new_filename' => 'file.jpg',
    ];
});

$factory->define(App\Models\Account\Place::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'country' => 'US',
        'street' => '12',
        'city' => 'beverly hills',
        'province' => null,
        'postal_code' => '90210',
    ];
});

$factory->define(App\Models\Account\Weather::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'place_id' => function (array $data) {
            return factory(App\Models\Account\Place::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'weather_json' => json_decode('
{
  "latitude": 45.487685,
  "longitude": -73.590259,
  "timezone": "America\/Toronto",
  "currently": {
    "time": 1541637005,
    "summary": "Mostly Cloudy",
    "icon": "partly-cloudy-night",
    "nearestStormDistance": 39,
    "nearestStormBearing": 307,
    "precipIntensity": 0,
    "precipProbability": 0,
    "temperature": 7.57,
    "apparentTemperature": 3.82,
    "dewPoint": 1.24,
    "humidity": 0.64,
    "pressure": 1009.91,
    "windSpeed": 6.98,
    "windGust": 12.99,
    "windBearing": 249,
    "cloudCover": 0.73,
    "uvIndex": 0,
    "visibility": 16.09,
    "ozone": 304.17
  },
  "offset": -5
}'),
        'created_at' => now(),
    ];
});
