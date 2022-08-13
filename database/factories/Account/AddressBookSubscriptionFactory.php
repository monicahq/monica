<?php

namespace Database\Factories\Account;

use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\AddressBook;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressBookSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AddressBookSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => factory(Account::class)->create(),
            'user_id' => function (array $attributes) {
                return factory(User::class)->create([
                    'account_id' => $attributes['account_id'],
                ]);
            },
            'address_book_id' => function (array $attributes) {
                return AddressBook::factory()->create([
                    'account_id' => $attributes['account_id'],
                    'user_id' => $attributes['user_id'],
                ]);
            },
            'name' => $this->faker->word,
            'uri' => $this->faker->url,
            'capabilities' => [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
            'username' => $this->faker->email,
            'password' => 'password',
            'syncToken' => '"test"',
        ];
    }
}
