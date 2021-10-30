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
            "location": {
                "name": "Le Pre-Saint-Gervais",
                "region": "Ile-de-France",
                "country": "France",
                "lat": 48.89,
                "lon": 2.39,
                "tz_id": "Europe\/Paris",
                "localtime_epoch": 1635605324,
                "localtime": "2021-10-30 16:48"
            },
            "current": {
                "last_updated_epoch": 1635605100,
                "last_updated": "2021-10-30 16:45",
                "temp_c": 13,
                "temp_f": 55.4,
                "is_day": 0,
                "condition": {
                    "text": "Partly cloudy",
                    "icon": "\/\/cdn.weatherapi.com\/weather\/64x64\/night\/116.png",
                    "code": 1003
                },
                "wind_mph": 5.6,
                "wind_kph": 9,
                "wind_degree": 210,
                "wind_dir": "SSW",
                "pressure_mb": 1001,
                "pressure_in": 29.56,
                "precip_mm": 0,
                "precip_in": 0,
                "humidity": 94,
                "cloud": 75,
                "feelslike_c": 11.2,
                "feelslike_f": 52.1,
                "vis_km": 10,
                "vis_miles": 6,
                "uv": 4,
                "gust_mph": 17.9,
                "gust_kph": 28.8
            }
        }'),
        'created_at' => now(),
    ];
});
