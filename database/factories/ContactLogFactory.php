<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ContactLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'author_id' => User::factory(),
            'author_name' => 'Dwight Schrute',
            'action_name' => 'account_created',
            'objects' => '{"user": 1}',
        ];
    }
}
