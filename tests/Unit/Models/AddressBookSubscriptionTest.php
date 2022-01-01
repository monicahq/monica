<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\AddressBook;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressBookSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($addressBookSubscription->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($addressBookSubscription->user()->exists());
    }

    /** @test */
    public function it_belongs_to_an_addressbook()
    {
        $addressBook = AddressBook::factory()->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'address_book_id' => $addressBook->id,
        ]);

        $this->assertTrue($addressBookSubscription->addressBook()->exists());
    }

    /** @test */
    public function it_saves_capabilities()
    {
        $addressBookSubscription = new AddressBookSubscription();

        $addressBookSubscription->capabilities = [
            'test' => true,
        ];

        $this->assertIsArray($addressBookSubscription->capabilities);
        $this->assertEquals([
            'test' => true,
        ], $addressBookSubscription->capabilities);
    }

    /** @test */
    public function it_saves_password()
    {
        $addressBookSubscription = new AddressBookSubscription();

        $addressBookSubscription->password = 'test';
        $this->assertEquals('test', $addressBookSubscription->password);
    }
}
