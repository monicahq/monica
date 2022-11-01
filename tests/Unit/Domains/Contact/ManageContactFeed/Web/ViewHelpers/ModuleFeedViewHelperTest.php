<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Models\Contact;
use App\Models\ContactFeedItem;
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
        ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $items = ContactFeedItem::where('contact_id', $contact->id)
            ->with('author')
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->get();

        $array = ModuleFeedViewHelper::data($items, $user, $contact->vault);

        $this->assertEquals(
            1,
            count($array)
        );

        $this->assertArrayHasKey('items', $array);
    }
}
