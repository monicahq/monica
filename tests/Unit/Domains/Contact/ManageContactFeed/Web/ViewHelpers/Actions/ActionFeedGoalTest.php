<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedGoal;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Pet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedGoalTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $goal = Pet::factory()->create([
            'contact_id' => $contact->id,
            'name' => 'Be awesome',
        ]);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_GOAL_CREATED,
            'description' => 'Be awesome',
        ]);
        $goal->feedItem()->save($feedItem);

        $array = ActionFeedGoal::data($feedItem);

        $this->assertEquals(
            [
                'goal' => [
                    'object' => [
                        'id' => $goal->id,
                        'name' => 'Be awesome',
                    ],
                    'description' => 'Be awesome',
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
