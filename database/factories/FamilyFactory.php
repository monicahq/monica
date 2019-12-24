<?php

$factory->define(App\Models\Family\Family::class, function () {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => 'John',
    ];
});
