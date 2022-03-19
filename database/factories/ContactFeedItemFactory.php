<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactFeedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFeedItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactFeedItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'action' => ContactFeedItem::ACTION_NOTE_CREATED,
        ];
    }
}
