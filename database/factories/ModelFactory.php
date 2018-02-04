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

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
        'timezone' => config('app.timezone'),
        'name_order' => 'firstname_first',
        'account_id' => factory('App\Account')->create()->id,
    ];
});

$factory->define(App\Account::class, function (Faker\Generator $faker) {
    return [
        'api_key' => str_random(30),
    ];
});

$factory->define(App\Activity::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'activity_type_id' => function () {
            return factory(App\ActivityType::class)->create()->id;
        },
        'description' => $faker->sentence,
        'summary' => $faker->sentence,
        'date_it_happened' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\ActivityType::class, function (Faker\Generator $faker) {
    return [
        'key' => $faker->sentence,
        'location_type' => $faker->word,
        'icon' => $faker->word,
    ];
});

$factory->define(App\Reminder::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'contact_id' => 1,
    ];
});

$factory->define(App\Contact::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'has_avatar' => false,
    ];
});

$factory->define(App\Gift::class, function (Faker\Generator $faker) {
    return [
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Call::class, function (Faker\Generator $faker) {
    return [
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Task::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'contact_id' => 1,
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\SpecialDate::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'contact_id' => 1,
        'date' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Note::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'body' => encrypt($faker->text(200)),
    ];
});

$factory->define(App\Relationship::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Offspring::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Country::class, function (Faker\Generator $faker) {
    return [
        'iso' => 'ca',
        'country' => 'Mali',
    ];
});

$factory->define(App\Call::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Invitation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Address::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Gender::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Entry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Day::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Progenitor::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Tag::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\JournalEntry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\Pet::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
    ];
});

$factory->define(App\PetCategory::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\ContactFieldType::class, function (Faker\Generator $faker) {
    return [
        'id' => 1,
        'account_id' => 1,
        'name' => 'Email',
        'protocol' => 'mailto:',
        'type' => 'email',
    ];
});

$factory->define(App\ContactField::class, function (Faker\Generator $faker) {
    return [
        'account_id' => 1,
        'contact_field_type_id' => 1,
        'data' => 'john@doe.com',
    ];
});

$factory->define(\Laravel\Cashier\Subscription::class, function (Faker\Generator $faker) {
    static $account_id;
    static $stripe_plan;
    static $name;
    static $stripe_id;

    return [
        'account_id' => $account_id,
        'name' => $name ?: $faker->randomElement(['main']),
        'stripe_id' => $stripe_id,
        'stripe_plan' => $stripe_plan ?: $faker->randomElement(['plan-1', 'plan-2', 'plan-3']),
        'quantity' => 1,
        'created_at' => \Carbon\Carbon::now(),
    ];
});
