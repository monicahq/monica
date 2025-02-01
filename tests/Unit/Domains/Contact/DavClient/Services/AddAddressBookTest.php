<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services;

use App\Domains\Contact\DavClient\Services\CreateAddressBookSubscription;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookGetter;
use App\Models\AddressBookSubscription;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

use function Safe\json_encode;

class AddAddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_addressbook()
    {
        $user = User::factory()->create();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $vault->update([
            'name' => 'contacts1',
        ]);

        $this->mock(AddressBookGetter::class, function (MockInterface $mock) {
            $mock->shouldReceive('withClient')->andReturnSelf();
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn($this->mockReturn());
        });

        $request = [
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'author_id' => $user->id,
            'base_uri' => 'https://test',
            'username' => 'test',
            'password' => 'test',
        ];

        $subscription = (new CreateAddressBookSubscription)->execute($request);

        $this->assertDatabaseHas('addressbook_subscriptions', [
            'id' => $subscription->id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'username' => 'test',
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

        $addressBookPassword = DB::table('addressbook_subscriptions')
            ->where('id', $subscription->id)
            ->select('password')
            ->get();
        $this->assertEquals('test', decrypt($addressBookPassword[0]->password));

        $this->assertInstanceOf(
            AddressBookSubscription::class,
            $subscription
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
