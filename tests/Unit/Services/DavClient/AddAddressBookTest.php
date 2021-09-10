<?php

namespace Tests\Unit\Services\DavClient;

use Tests\TestCase;
use App\Models\User\User;
use Tests\Helpers\DavTester;
use App\Services\DavClient\AddAddressBook;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\DAVClient\Dav\DavClientException;
use App\Http\Controllers\DAVClient\Dav\DavServerNotCompliantException;

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
        $addressBook = app(AddAddressBook::class)->execute($request, $tester->getClient());

        $this->assertDatabaseHas('addressbooks', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('addressbook_subscriptions', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'address_book_id' => $addressBook->id,
        ]);

        $this->assertInstanceOf(
            AddressBookSubscription::class,
            $addressBook
        );
    }

    /** @test */
    public function it_fails_on_server_not_compliant()
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
            ->serviceUrl()
            ->optionsFail();

        $this->expectException(DavServerNotCompliantException::class);
        app(AddAddressBook::class)->execute($request, $tester->getClient());
    }

    /** @test */
    public function it_fails_if_no_userprincipal()
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
            ->serviceUrl()
            ->options()
            ->userPrincipalEmpty();

        $this->expectException(DavServerNotCompliantException::class);
        app(AddAddressBook::class)->execute($request, $tester->getClient());
    }

    /** @test */
    public function it_fails_if_no_addressbook()
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
            ->serviceUrl()
            ->options()
            ->userPrincipal()
            ->addressbookEmpty();

        $this->expectException(DavServerNotCompliantException::class);
        app(AddAddressBook::class)->execute($request, $tester->getClient());
    }

    /** @test */
    public function it_fails_if_no_addressbook_url()
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
            ->serviceUrl()
            ->options()
            ->userPrincipal()
            ->addressbookHome()
            ->resourceTypeHomeOnly();

        $this->expectException(DavClientException::class);
        app(AddAddressBook::class)->execute($request, $tester->getClient());
    }
}
