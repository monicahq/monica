<?php

$factory->define(App\Models\Group\Group::class, function () {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => 'All my friends',
    ];
});
