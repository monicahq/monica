<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedMoodTrackingEvent;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\MoodTrackingEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedMoodTrackingEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = $this->createAdministrator();
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'rated_at' => '2020-01-01 00:00:00',
            'note' => 'Be awesome',
            'number_of_hours_slept' => 3,
        ]);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_MOOD_TRACKING_EVENT_CREATED,
            'description' => 'Be awesome',
        ]);
        $moodTrackingEvent->feedItem()->save($feedItem);

        $array = ActionFeedMoodTrackingEvent::data($feedItem, $user);

        $this->assertEquals(
            [
                'mood_tracking_event' => [
                    'object' => [
                        'id' => $moodTrackingEvent->id,
                        'rated_at' => 'Jan 01, 2020',
                        'note' => 'Be awesome',
                        'number_of_hours_slept' => 3,
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
