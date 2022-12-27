<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'vault_id' => Vault::factory(),
            'author_id' => User::factory(),
            'title' => $this->faker->title(),
            'body' => $this->faker->sentence(),
        ];
    }
}
