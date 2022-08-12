<?php

namespace Database\Factories\Account;

use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\AddressBook;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressBookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AddressBook::class;

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
            'name' => 'contacts1',
            'description' => $this->faker->sentence,
        ];
    }
}
