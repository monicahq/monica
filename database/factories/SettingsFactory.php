<?php

$factory->define(App\Models\Settings\Term::class, function (Faker\Generator $faker) {
    return [
        'term_version' => $faker->realText(50),
        'term_content' => $faker->realText(50),
        'privacy_version' => $faker->realText(50),
        'privacy_content' => $faker->realText(50),
    ];
});

$factory->define(App\Models\Settings\Currency::class, function (Faker\Generator $faker) {
    return [
        'iso' => $faker->realText(10),
        'name' => $faker->realText(10),
        'symbol' => $faker->realText(10),
    ];
});

$factory->define(\Laravel\Cashier\Subscription::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => $faker->word(),
        'stripe_id' => $faker->word(),
        'stripe_price' => $faker->randomElement(['plan-1', 'plan-2', 'plan-3']),
        'quantity' => 1,
        'created_at' => now(),
    ];
});
