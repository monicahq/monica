<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'uuid' => $this->faker->uuid,
            'original_url' => $this->faker->url,
            'cdn_url' => $this->faker->url,
            'name' => $this->faker->name,
            'mime_type' => 'avatar',
            'type' => 'avatar',
            'size' => $this->faker->numberBetween(200 * 1024 * 1024, 500 * 1024 * 1024),
        ];
    }
}
