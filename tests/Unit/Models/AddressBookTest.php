<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\AddressBook;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $addressBook = AddressBook::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($addressBook->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = factory(User::class)->create();
        $addressBook = AddressBook::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($addressBook->user()->exists());
    }
}
