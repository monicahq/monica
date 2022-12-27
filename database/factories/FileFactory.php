<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\File;
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
            'contact_id' => Contact::factory(),
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
