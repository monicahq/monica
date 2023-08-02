<?php

namespace Database\Factories;

use App\Models\AddressBookSubscription;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddressBookSubscription>
 */
class AddressBookSubscriptionFactory extends Factory
{
    protected $model = AddressBookSubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'vault_id' => fn ($attributes) => Vault::factory()->create([
                'account_id' => User::find($attributes['user_id'])->account_id,
            ]),
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
            'distant_sync_token' => '"test"',
            'active' => true,
            'sync_way' => AddressBookSubscription::WAY_BOTH,
        ];
    }

    /**
     * Indicate that the subscription is readonly.
     */
    public function readonly()
    {
        return $this->state(fn () => [
            'readonly' => true,
        ]);
    }

    /**
     * Indicate that the subscription is not active.
     */
    public function inactive()
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }
}
