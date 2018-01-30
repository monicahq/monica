<?php

namespace Tests\Unit;

use App\Gender;
use App\Account;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($gender->account()->exists());
    }

    public function test_it_gets_the_gender_name()
    {
        $gender = new Gender;
        $gender->name = 'Woman';

        $this->assertEquals(
            'Woman',
            $gender->name
        );
    }
}
