<?php

namespace Tests\Unit\Models;

use App\Models\ContactFeedItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactFeedItemTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_user()
    {
        $feedItem = ContactFeedItem::factory()->create();

        $this->assertTrue($feedItem->author()->exists());
    }

    /** @test */
    public function it_has_one_contact()
    {
        $feedItem = ContactFeedItem::factory()->create();

        $this->assertTrue($feedItem->contact()->exists());
    }
}
