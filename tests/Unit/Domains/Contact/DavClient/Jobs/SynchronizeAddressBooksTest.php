<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Jobs\SynchronizeAddressBooks;
use App\Domains\Contact\DavClient\Services\SynchronizeAddressBook;
use App\Models\AddressBookSubscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SynchronizeAddressBooksTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_card()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $subscription = AddressBookSubscription::factory()->create([
            'last_synchronized_at' => null,
        ]);

        $this->mock(SynchronizeAddressBook::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('execute')->once()->with([
                'account_id' => $subscription->user->account_id,
                'addressbook_subscription_id' => $subscription->id,
                'force' => false,
            ]);
        });

        (new SynchronizeAddressBooks($subscription, false))->handle();

        $this->assertEquals('2021-01-01 00:00:00', $subscription->fresh()->last_synchronized_at);
    }
}
