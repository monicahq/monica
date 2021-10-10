<?php

use Illuminate\Support\Str;

$factory->define(App\Models\Contact\Contact::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'has_avatar' => false,
        'gender_id' => function (array $data) {
            return factory(App\Models\Contact\Gender::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'uuid' => Str::uuid(),
        'default_avatar_color' => '#ffffff',
        'avatar_default_url' => 'avatars/img.png',
    ];
});

$factory->state(App\Models\Contact\Contact::class, 'partial', [
    'is_partial' => 1,
]);

$factory->state(App\Models\Contact\Contact::class, 'archived', [
    'is_active' => 0,
]);

$factory->state(App\Models\Contact\Contact::class, 'named', function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});

$factory->state(App\Models\Contact\Contact::class, 'no_gender', function (Faker\Generator $faker) {
    return [
        'gender_id' => null,
    ];
});

$factory->define(App\Models\Contact\ContactFieldType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => 'Email',
        'protocol' => 'mailto:',
        'type' => 'email',
    ];
});

$factory->define(App\Models\Contact\ContactField::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'contact_field_type_id' => function (array $data) {
            return factory(App\Models\Contact\ContactFieldType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'data' => 'john@doe.com',
    ];
});

$factory->define(App\Models\Contact\ContactFieldLabel::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'label_i18n' => 'work',
    ];
});

$factory->define(App\Models\Contact\Conversation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'contact_field_type_id' => function (array $data) {
            return factory(App\Models\Contact\ContactFieldType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Contact\Reminder::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Contact\ReminderOutbox::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'user_id' => function (array $data) {
            return factory(App\Models\User\User::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'reminder_id' => function (array $data) {
            return factory(App\Models\Contact\Reminder::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'planned_date' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Contact\Gift::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'status' => 'idea',
        'created_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Contact\Call::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'created_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Contact\Task::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'title' => $faker->word,
        'description' => $faker->word,
        'completed' => 0,
        'created_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
        'uuid' => Str::uuid(),
    ];
});

$factory->define(App\Models\Contact\Note::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'body' => encrypt($faker->text(200)),
    ];
});

$factory->define(App\Models\Contact\Address::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'place_id' => function (array $data) {
            return factory(App\Models\Account\Place::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Contact\Gender::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'type' => 'M',
        'name' => 'Man',
    ];
});

$factory->define(App\Models\Contact\Debt::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Contact\Tag::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => $faker->word,
        'name_slug' => Str::slug($faker->word),
    ];
});

$factory->define(App\Models\Contact\Pet::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'pet_category_id' => factory(App\Models\Contact\PetCategory::class)->create()->id,
    ];
});

$factory->define(App\Models\Contact\PetCategory::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\Contact\ReminderRule::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Contact\Message::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'conversation_id' => function (array $data) {
            return factory(App\Models\Contact\Conversation::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\Contact\Document::class, function (Faker\Generator $faker) {
    $contact = factory(App\Models\Contact\Contact::class)->create();

    return [
        'account_id' => $contact->account_id,
        'contact_id' => $contact->id,
        'original_filename' => 'file.jpg',
        'new_filename' => 'file.jpg',
    ];
});

$factory->define(App\Models\Contact\Occupation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'company_id' => function (array $data) {
            return factory(App\Models\Account\Company::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'title' => 'Waiter',
        'salary' => '10000',
        'salary_unit' => 'year',
    ];
});

$factory->define(App\Models\Contact\LifeEventCategory::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => $faker->text(100),
        'core_monica_data' => true,
    ];
});

$factory->define(App\Models\Contact\LifeEventType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'life_event_category_id' => function (array $data) {
            return factory(App\Models\Contact\LifeEventCategory::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'name' => $faker->text(100),
        'core_monica_data' => true,
    ];
});

$factory->define(App\Models\Contact\LifeEvent::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'life_event_type_id' => function (array $data) {
            return factory(App\Models\Contact\LifeEventType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'name' => $faker->text(100),
        'note' => $faker->text(100),
        'happened_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});
