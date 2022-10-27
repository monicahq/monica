<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'name' => $this->faker->name(),
            'slug' => $this->faker->name(),
        ];
    }
}
