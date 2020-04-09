<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GiftTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function has_particular_recipient_returns_false_if_it_s_for_no_specific_recipient()
    {
        $gift = factory(Gift::class)->make();

        $this->assertEquals(
            false,
            $gift->hasParticularRecipient()
        );
    }

    /** @test */
    public function has_particular_recipient_returns_true_if_it_s_for_a_specific_recipient()
    {
        $gift = factory(Gift::class)->make([
            'is_for' => 1,
        ]);

        $this->assertEquals(
            true,
            $gift->hasParticularRecipient()
        );
    }

    /** @test */
    public function it_sets_is_for_attribute()
    {
        $gift = factory(Gift::class)->make([
            'is_for' => 1,
        ]);

        $this->assertEquals(
            1,
            $gift->is_for
        );
    }

    /** @test */
    public function it_gets_the_recipient_name()
    {
        $contact = factory(Contact::class)->create(['first_name' => 'Regis']);
        $gift = factory(Gift::class)->make([
            'account_id' => $contact->account_id,
            'is_for' => $contact->id,
            'contact_id' =>  $contact->id,
        ]);

        $this->assertEquals(
            'Regis',
            $gift->recipient_name
        );
    }

    /** @test */
    public function it_gets_the_gift_name()
    {
        $gift = factory(Gift::class)->make([
            'name' => 'Maison de folie',
        ]);

        $this->assertEquals(
            'Maison de folie',
            $gift->name
        );
    }

    /** @test */
    public function it_gets_the_gift_url()
    {
        $gift = factory(Gift::class)->make([
            'url' => 'https://facebook.com',
        ]);

        $this->assertEquals(
            'https://facebook.com',
            $gift->url
        );
    }

    /** @test */
    public function it_gets_the_comment()
    {
        $gift = factory(Gift::class)->make([
            'comment' => 'This is just a comment',
        ]);

        $this->assertEquals(
            'This is just a comment',
            $gift->comment
        );
    }

    /** @test */
    public function it_gets_the_value()
    {
        $gift = factory(Gift::class)->make([
            'value' => 100,
        ]);

        $this->assertEquals(
            '100',
            $gift->amount
        );
    }
}
