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

$factory->define(Lahaxearnaud\U2f\Models\U2fKey::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'user_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'keyHandle' => $faker->word,
        'publicKey' => $faker->word,
        'certificate' => $faker->word,
        'counter' => 0,
    ];
});
