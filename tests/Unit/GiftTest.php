<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GiftTest extends TestCase
{
    use DatabaseTransactions;

    public function test_toggle_a_gift_idea()
    {
        $gift = factory(Gift::class)->make();
        $gift->is_an_idea = true;
        $gift->toggle();

        $this->assertEquals(
            false,
            $gift->is_an_idea
        );

        $this->assertEquals(
            true,
            $gift->has_been_offered
        );

        $this->assertEquals(
            false,
            $gift->has_been_received
        );
    }

    public function test_toggle_a_gift_offered()
    {
        $gift = factory(Gift::class)->make();
        $gift->has_been_offered = true;
        $gift->toggle();

        $this->assertEquals(
            true,
            $gift->is_an_idea
        );

        $this->assertEquals(
            false,
            $gift->has_been_offered
        );

        $this->assertEquals(
            false,
            $gift->has_been_received
        );
    }

    public function test_has_particular_recipient_returns_false_if_it_s_for_no_specific_recipient()
    {
        $gift = factory(Gift::class)->make();

        $this->assertEquals(
            false,
            $gift->hasParticularRecipient()
        );
    }

    public function test_has_particular_recipient_returns_true_if_it_s_for_a_specific_recipient()
    {
        $gift = factory(Gift::class)->make();
        $gift->is_for = 1;

        $this->assertEquals(
            true,
            $gift->hasParticularRecipient()
        );
    }

    public function test_it_sets_is_for_attribute()
    {
        $gift = factory(Gift::class)->make();
        $gift->is_for = 1;

        $this->assertEquals(
            1,
            $gift->is_for
        );
    }

    public function test_it_gets_the_recipient_name()
    {
        $gift = factory(Gift::class)->make();
        $gift->is_for = 1;
        $gift->contact_id = 1;

        $contact = factory(Contact::class)->create(['id' => 1, 'first_name' => 'Regis']);

        $this->assertEquals(
            'Regis',
            $gift->recipient_name
        );
    }

    public function test_it_gets_the_gift_name()
    {
        $gift = factory(Gift::class)->make();
        $gift->name = 'Maison de folie';

        $this->assertEquals(
            'Maison de folie',
            $gift->name
        );
    }

    public function test_it_gets_the_gift_url()
    {
        $gift = factory(Gift::class)->make();
        $gift->url = 'https://facebook.com';

        $this->assertEquals(
            'https://facebook.com',
            $gift->url
        );
    }

    public function test_it_gets_the_comment()
    {
        $gift = factory(Gift::class)->make();
        $gift->comment = 'This is just a comment';

        $this->assertEquals(
            'This is just a comment',
            $gift->comment
        );
    }

    public function test_it_gets_the_value()
    {
        $gift = factory(Gift::class)->make();
        $gift->value = '100$';

        $this->assertEquals(
            '100$',
            $gift->value
        );
    }
}
