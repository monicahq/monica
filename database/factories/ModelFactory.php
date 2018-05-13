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
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Account::class, function (Faker\Generator $faker) {
    return [
        'api_key' => str_random(30),
    ];
});

$factory->define(App\Activity::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
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
        'activity_type_group_id' => function () {
            return factory(App\ActivityTypeGroup::class)->create()->id;
        },
        'key' => $faker->sentence,
        'location_type' => $faker->word,
        'icon' => $faker->word,
    ];
});

$factory->define(App\ActivityTypeGroup::class, function (Faker\Generator $faker) {
    return [
        'key' => $faker->sentence,
    ];
});

$factory->define(App\Reminder::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
    ];
});

$factory->define(App\Contact::class, function (Faker\Generator $faker) {
    return [
        'account_id' => function () {
            return factory(App\Account::class)->create()->id;
        },
        'first_name' => 'John',
        'last_name' => 'Doe',
        'has_avatar' => false,
        'gender_id' => function () {
            return factory(App\Gender::class)->create()->id;
        },
    ];
});

$factory->define(App\Gift::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Call::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Task::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\SpecialDate::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'date' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
        'created_at' => \Carbon\Carbon::createFromTimeStamp($faker->dateTimeThisCentury()->getTimeStamp()),
    ];
});

$factory->define(App\Note::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'body' => encrypt($faker->text(200)),
    ];
});

$factory->define(App\Relationship::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
        'relationship_type_id' => function () {
            return factory(App\RelationshipType::class)->create()->id;
        },
    ];
});

$factory->define(App\RelationshipType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
        'relationship_type_group_id' => function () {
            return factory(App\RelationshipTypeGroup::class)->create()->id;
        },
    ];
});

$factory->define(App\RelationshipTypeGroup::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Offspring::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Call::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Invitation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Address::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Gender::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Entry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Debt::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
    ];
});

$factory->define(App\Day::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Progenitor::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Tag::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\JournalEntry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Pet::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'pet_category_id' => factory(App\PetCategory::class)->create()->id,
    ];
});

$factory->define(App\PetCategory::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\ContactFieldType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
        'name' => 'Email',
        'protocol' => 'mailto:',
        'type' => 'email',
    ];
});

$factory->define(App\ContactField::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'contact_field_type_id' => 1,
        'data' => 'john@doe.com',
    ];
});

$factory->define(App\ReminderRule::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Notification::class, function (Faker\Generator $faker) {
    $contact = factory(App\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
    ];
});

$factory->define(App\Module::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Account::class)->create()->id,
    ];
});

$factory->define(App\Changelog::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Instance::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\ImportJob::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\ImportJobReport::class, function (Faker\Generator $faker) {
    return [];
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
        'created_at' => now(),
    ];
});
