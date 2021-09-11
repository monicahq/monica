<?php

namespace Tests\Unit\Services\DavClient;

use Tests\TestCase;
use App\Models\User\User;
use Tests\Helpers\DavTester;
use App\Models\Account\AddressBook;
use App\Services\DavClient\AddAddressBook;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddAddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_addressbook()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'base_uri' => 'https://test',
            'username' => 'test',
            'password' => 'test',
        ];

        $tester = (new DavTester())
            ->addressBookBaseUri()
            ->capabilities()
            ->displayName();
        $addressBookSubscription = app(AddAddressBook::class)->execute($request, $tester->getClient());

        $tester->assert();
        $this->assertDatabaseHas('addressbooks', [
            'id' => $addressBookSubscription->address_book_id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts1',
        ]);
        $this->assertDatabaseHas('addressbook_subscriptions', [
            'id' => $addressBookSubscription->id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'address_book_id' => $addressBookSubscription->address_book_id,
            'capabilities' => json_encode([
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ]),
        ]);

        $this->assertInstanceOf(
            AddressBookSubscription::class,
            $addressBookSubscription
        );
    }

    /** @test */
    public function it_creates_next_addressbook()
    {
        $user = factory(User::class)->create([]);
        AddressBook::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts5',
        ]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'base_uri' => 'https://test',
            'username' => 'test',
            'password' => 'test',
        ];

        $tester = (new DavTester())
            ->addressBookBaseUri()
            ->capabilities()
            ->displayName();
        $addressBookSubscription = app(AddAddressBook::class)->execute($request, $tester->getClient());

        $tester->assert();
        $this->assertDatabaseHas('addressbooks', [
            'id' => $addressBookSubscription->address_book_id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts6',
        ]);
        $this->assertDatabaseHas('addressbook_subscriptions', [
            'id' => $addressBookSubscription->id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'address_book_id' => $addressBookSubscription->address_book_id,
        ]);

        $this->assertInstanceOf(
            AddressBookSubscription::class,
            $addressBookSubscription
        );
    }
}
