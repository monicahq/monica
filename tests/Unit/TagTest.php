<?php

namespace Tests\Unit;

use App\Tag;
use App\Account;
use App\Contact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $tag = factory('App\Tag')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($task->account()->exists());
    }
}
