<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
            'size' => $this->faker->numberBetween(),
        ];
    }
}
