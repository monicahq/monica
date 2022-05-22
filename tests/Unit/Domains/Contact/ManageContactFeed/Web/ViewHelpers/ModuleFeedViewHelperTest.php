<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers;

use App\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleFeedViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $note = Note::factory()->create();
        ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_NOTE_CREATED,
            'feedable_id' => $note->id,
            'feedable_type' => Note::class,
        ]);

        $array = ModuleFeedViewHelper::data($contact, $user);

        $this->assertEquals(
            1,
            count($array)
        );

        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('id', $array['items']->toArray()[0]);
        $this->assertArrayHasKey('action', $array['items']->toArray()[0]);
        $this->assertArrayHasKey('description', $array['items']->toArray()[0]);
    }
}
