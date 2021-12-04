<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactFeedItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFeedItemTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $feedItem = ContactFeedItem::factory()->create();

        $this->assertTrue($feedItem->contact()->exists());
    }
}
