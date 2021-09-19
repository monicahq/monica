<?php

namespace Tests\Unit\Services\DavClient;

use Tests\TestCase;
use App\Models\User\User;
use Mockery\MockInterface;
use function Safe\json_encode;
use App\Models\Account\AddressBook;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\AddressBookGetter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\CreateAddressBookSubscription;

class AddAddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_addressbook()
    {
        $user = factory(User::class)->create([]);

        $this->mock(AddressBookGetter::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn($this->mockReturn());
        });

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'base_uri' => 'https://test',
            'username' => 'test',
            'password' => 'test',
        ];

        $addressBookSubscription = (new CreateAddressBookSubscription())->execute($request);

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

        $this->mock(AddressBookGetter::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn($this->mockReturn());
        });

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'base_uri' => 'https://test',
            'username' => 'test',
            'password' => 'test',
        ];

        $addressBookSubscription = app(CreateAddressBookSubscription::class)->execute($request);

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

    private function mockReturn(): array
    {
        return [
            'uri' => 'https://test/dav',
            'capabilities' => [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
            'name' => 'Test',
        ];
    }
}
