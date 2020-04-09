<?php

use Illuminate\Support\Str;

$factory->define(App\Models\User\User::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
        'password' => bcrypt(Str::random(10)),
        'remember_token' => Str::random(10),
        'timezone' => config('app.timezone'),
        'name_order' => 'firstname_lastname',
        'locale' => 'en',
    ];
});

$factory->define(App\Models\User\Changelog::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\User\Module::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\User\SyncToken::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'user_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'timestamp' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});
