<?php

$factory->define(App\Models\Relationship\Relationship::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'relationship_type_id' => function (array $data) {
            return factory(App\Models\Relationship\RelationshipType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'contact_is' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'of_contact' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Relationship\RelationshipType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'relationship_type_group_id' => function (array $data) {
            return factory(App\Models\Relationship\RelationshipTypeGroup::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Relationship\RelationshipTypeGroup::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});
