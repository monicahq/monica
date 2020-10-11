<?php

$factory->define(App\Models\Journal\Entry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Journal\Day::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Journal\JournalEntry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});
