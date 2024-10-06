<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services;

use App\Domains\Contact\DavClient\Services\SynchronizeAddressBook;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookSynchronizer;
use App\Models\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;

class SynchronizeAddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_runs_sync()
    {
        $this->mock(AddressBookSynchronizer::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturnSelf();
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($force) {
                    $this->assertFalse($force);

                    return true;
                });
        });

        $subscription = AddressBookSubscription::factory()->create();

        $request = [
            'account_id' => $subscription->user->account_id,
            'addressbook_subscription_id' => $subscription->id,
        ];

        (new SynchronizeAddressBook)->execute($request);
    }

    /** @test */
    public function it_runs_sync_force()
    {
        $this->mock(AddressBookSynchronizer::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturnSelf();
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($force) {
                    $this->assertTrue($force);

                    return true;
                });
        });

        $subscription = AddressBookSubscription::factory()->create();

        $request = [
            'account_id' => $subscription->user->account_id,
            'addressbook_subscription_id' => $subscription->id,
            'force' => true,
        ];

        (new SynchronizeAddressBook)->execute($request);
    }
}
