<?php

$factory->define(\App\Models\Instance\Cron::class, function (Faker\Generator $faker) {
    return [
        'command' => $faker->word,
        'last_run' => now(),
    ];
});

$factory->define(App\Models\Instance\SpecialDate::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'date' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
        'created_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Instance\Instance::class, function (Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid,
        'latest_version' => '1.0.0',
        'current_version' => '1.0.0',
    ];
});

$factory->define(App\Models\Instance\Emotion\Emotion::class, function (Faker\Generator $faker) {
    return [
        'emotion_primary_id' => factory(App\Models\Instance\Emotion\PrimaryEmotion::class)->create()->id,
        'emotion_secondary_id' => function (array $data) {
            return factory(App\Models\Instance\Emotion\SecondaryEmotion::class)->create([
                'emotion_primary_id' => $data['emotion_primary_id'],
            ])->id;
        },
        'name' => $faker->text(5),
    ];
});

$factory->define(App\Models\Instance\Emotion\SecondaryEmotion::class, function (Faker\Generator $faker) {
    return [
        'emotion_primary_id' => factory(App\Models\Instance\Emotion\PrimaryEmotion::class)->create()->id,
        'name' => $faker->text(5),
    ];
});

$factory->define(App\Models\Instance\Emotion\PrimaryEmotion::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->text(5),
    ];
});

$factory->define(App\Models\Instance\AuditLog::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'author_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'action' => 'account_created',
        'author_name' => 'Dwight Schrute',
        'audited_at' => $faker->dateTimeThisCentury(),
        'objects' => '{"user": 1}',
    ];
});
