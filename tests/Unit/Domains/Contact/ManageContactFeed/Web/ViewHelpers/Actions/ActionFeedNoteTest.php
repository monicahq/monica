<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedNote;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Note;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedNoteTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'title' => 'Charles',
            'body' => 'This is incredible and I like it very much Cillum duis dolore commodo occaecat commodo qui. Irure sint nisi excepteur et cillum. Incididunt ad velit sit ut nostrud id eu esse tempor. Ut aliquip esse amet est nulla consectetur nulla occaecat commodo commodo deserunt non minim. Aute veniam dolor qui id. Amet reprehenderit Lorem proident aliqua velit aliqua consectetur incididunt culpa.',
        ]);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_NOTE_CREATED,
            'description' => 'pet',
        ]);
        $note->feedItem()->save($feedItem);

        $array = ActionFeedNote::data($feedItem);

        $this->assertEquals(
            [
                'note' => [
                    'object' => [
                        'id' => $note->id,
                        'title' => 'Charles',
                        'body' => 'This is incredible and I like...',
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
