<?php

namespace Tests\Unit;

use App\Tag;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $tag = factory('App\Tag')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($tag->account()->exists());
    }

    public function test_it_belongs_to_many_contacts()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $tag = factory('App\Tag')->create(['account_id' => $account->id]);
        $contact->tags()->sync([$tag->id => ['account_id' => $account->id]]);

        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $tag = factory('App\Tag')->create(['account_id' => $account->id]);
        $contact->tags()->sync([$tag->id => ['account_id' => $account->id]]);

        $this->assertTrue($tag->contacts()->exists());
    }

    public function test_it_updates_the_slug()
    {
        $tag = factory(Tag::class)->create(['name' => 'this is great']);
        $tag->updateSlug();
        $this->assertEquals(
            'this-is-great',
            $tag->name_slug
        );
    }
}
