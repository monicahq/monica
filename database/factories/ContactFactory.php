<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Gender;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'first_name' => 'Regis',
            'last_name' => 'Troyat',
            'can_be_deleted' => true,
            'prefix' => 'Dr.',
            'suffix' => 'III',
            'gender_id' => fn (array $properties) => Gender::factory()->create([
                'account_id' => Vault::find($properties['vault_id'])->account_id,
            ])->getKey(),
        ];
    }

    /**
     * Use random first and last name.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function random()
    {
        return $this->state(fn () => [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);
    }

    /**
     * Indicate that the contact has a middle name.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function middle()
    {
        return $this->state(fn () => [
            'middle_name' => $this->faker->firstName,
        ]);
    }

    /**
     * Indicate that the contact has a maiden name.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function maiden()
    {
        return $this->state(fn () => [
            'maiden_name' => $this->faker->unique()->name,
        ]);
    }

    /**
     * Indicate that the contact has a nickname.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function nickname()
    {
        return $this->state(fn () => [
            'nickname' => $this->faker->unique()->firstName,
        ]);
    }

    /**
     * Indicate that the contact has a job.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function company()
    {
        return $this->state(fn () => [
            'company_id' => Company::factory(),
        ]);
    }
}
