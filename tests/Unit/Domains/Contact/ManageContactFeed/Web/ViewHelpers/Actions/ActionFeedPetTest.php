<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedPet;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Pet;
use App\Models\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedPetTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $petCategory = PetCategory::factory()->create([
            'name' => 'Dog',
        ]);
        $pet = Pet::factory()->create([
            'pet_category_id' => $petCategory->id,
            'name' => 'Charles',
        ]);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_PET_CREATED,
            'description' => 'pet',
        ]);
        $pet->feedItem()->save($feedItem);

        $array = ActionFeedPet::data($feedItem);

        $this->assertEquals(
            [
                'pet' => [
                    'object' => [
                        'id' => $pet->id,
                        'name' => 'Charles',
                        'pet_category' => [
                            'id' => $petCategory->id,
                            'name' => 'Dog',
                        ],
                    ],
                    'description' => 'pet',
                ],
                'contact' => [
                    'id' => $contact->id,
                    'name' => 'John Doe',
                    'age' => null,
                    'avatar' => $contact->avatar,
                    'url' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
                ],
            ],
            $array
        );
    }
}
