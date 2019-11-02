<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($gender->account()->exists());
    }

    public function test_it_belongs_to_many_contacts()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender->id]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender->id]);

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

    public function test_it_gets_the_default_gender()
    {
        $account = factory(Account::class)->create();
        $gender = Gender::create([
            'account_id' => $account->id,
            'name' => 'Woman',
        ]);

        $this->assertFalse($gender->isDefault());

        $account->default_gender_id = $gender->id;
        $account->save();
        $gender->refresh();

        $this->assertTrue($gender->isDefault());
    }
}
