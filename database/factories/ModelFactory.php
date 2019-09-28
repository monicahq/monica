<?php

use Illuminate\Support\Str;
use function Safe\json_decode;

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

$factory->define(App\Models\User\User::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->safeEmail,
        'email_verified_at' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
        'password' => bcrypt(Str::random(10)),
        'remember_token' => Str::random(10),
        'timezone' => config('app.timezone'),
        'name_order' => 'firstname_lastname',
        'locale' => 'en',
    ];
});

$factory->define(App\Models\Account\Account::class, function (Faker\Generator $faker) {
    return [
        'api_key' => Str::random(30),
    ];
});

$factory->define(App\Models\Account\Activity::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'activity_type_id' => function (array $data) {
            return factory(App\Models\Account\ActivityType::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'description' => $faker->sentence,
        'summary' => $faker->sentence,
        'date_it_happened' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});

$factory->define(App\Models\Account\ActivityType::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'activity_type_category_id' => function (array $data) {
            return factory(App\Models\Account\ActivityTypeCategory::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'translation_key' => $faker->sentence,
        'location_type' => $faker->word,
    ];
});

$factory->define(App\Models\Account\ActivityTypeCategory::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'translation_key' => $faker->sentence,
        'name' => $faker->sentence,
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

$factory->define(App\Models\Contact\ReminderSent::class, function (Faker\Generator $faker) {
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

$factory->define(App\Models\Contact\Gift::class, function (Faker\Generator $faker) {
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

$factory->define(App\Offspring::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Contact\Call::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Account\Invitation::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
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

$factory->define(App\Models\Journal\Entry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
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

$factory->define(App\Models\Journal\Day::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Contact\Tag::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => $faker->word,
        'name_slug' => Str::slug($faker->word),
    ];
});

$factory->define(App\Models\Journal\JournalEntry::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
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

$factory->define(App\Models\Contact\ReminderRule::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
    ];
});

$factory->define(App\Models\Contact\Notification::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'contact_id' => function (array $data) {
            return factory(App\Models\Contact\Contact::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
    ];
});

$factory->define(App\Models\User\Module::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
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

$factory->define(App\Models\Account\Photo::class, function (Faker\Generator $faker) {
    $account = factory(App\Models\Account\Account::class)->create();

    return [
        'account_id' => $account->id,
        'original_filename' => 'file.jpg',
        'new_filename' => 'file.jpg',
    ];
});

$factory->define(App\Models\Account\Place::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'country' => 'US',
        'street' => '12',
        'city' => 'beverly hills',
        'province' => null,
        'postal_code' => '90210',
    ];
});

$factory->define(App\Models\Account\Company::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'name' => 'Central Perk',
        'website' => 'https://centralperk.com',
        'number_of_employees' => 4,
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

$factory->define(App\Models\User\Changelog::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\Instance\Instance::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\Account\ImportJob::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(App\Models\Account\ImportJobReport::class, function (Faker\Generator $faker) {
    return [];
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

$factory->define(App\Models\Account\Weather::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'place_id' => function (array $data) {
            return factory(App\Models\Account\Place::class)->create([
                'account_id' => $data['account_id'],
            ])->id;
        },
        'weather_json' => json_decode('
{
  "latitude": 45.487685,
  "longitude": -73.590259,
  "timezone": "America\/Toronto",
  "currently": {
    "time": 1541637005,
    "summary": "Mostly Cloudy",
    "icon": "partly-cloudy-night",
    "nearestStormDistance": 39,
    "nearestStormBearing": 307,
    "precipIntensity": 0,
    "precipProbability": 0,
    "temperature": 7.57,
    "apparentTemperature": 3.82,
    "dewPoint": 1.24,
    "humidity": 0.64,
    "pressure": 1009.91,
    "windSpeed": 6.98,
    "windGust": 12.99,
    "windBearing": 249,
    "cloudCover": 0.73,
    "uvIndex": 0,
    "visibility": 16.09,
    "ozone": 304.17
  },
  "offset": -5
}'),
        'created_at' => now(),
    ];
});

$factory->define(App\Models\User\SyncToken::class, function (Faker\Generator $faker) {
    return [
        'account_id' => factory(App\Models\Account\Account::class)->create()->id,
        'user_id' => factory(App\Models\User\User::class)->create()->id,
        'timestamp' => \App\Helpers\DateHelper::parseDateTime($faker->dateTimeThisCentury()),
    ];
});
