<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(\App\Models\Instance\Cron::class, function (Faker\Generator $faker) {
    return [
        'command' => $faker->word,
        'last_run' => now(),
    ];
});
