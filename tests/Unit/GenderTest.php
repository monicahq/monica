<?php

namespace Tests\Unit;

use App\Gender;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($gender->account()->exists());
    }

    public function test_it_belongs_to_many_contacts()
    {
        $account = factory('App\Account')->create([]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id, 'gender_id' => $gender->id]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id, 'gender_id' => $gender->id]);

        $this->assertTrue($gender->contacts()->exists());
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
