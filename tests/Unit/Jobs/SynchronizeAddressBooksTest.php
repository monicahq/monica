<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Jobs\SynchronizeAddressBooks;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\SynchronizeAddressBook;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SynchronizeAddressBooksTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_run_synchronize()
    {
        Carbon::setTestNow(Carbon::create(2021, 9, 1, 10, 0, 0));

        $subscription = AddressBookSubscription::factory()->create();

        $this->mock(SynchronizeAddressBook::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('execute')
                ->once()
                ->with([
                    'account_id' => $subscription->account_id,
                    'addressbook_subscription_id' => $subscription->id,
                    'force' => false,
                ]);
        });

        (new SynchronizeAddressBooks($subscription))
            ->handle();

        $subscription->refresh();
        $this->assertEquals(Carbon::create(2021, 9, 1, 10, 0, 0), $subscription->last_synchronized_at);
    }
}
