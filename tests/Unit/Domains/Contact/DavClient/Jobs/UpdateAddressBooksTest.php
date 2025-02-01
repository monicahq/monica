<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Jobs\SynchronizeAddressBooks;
use App\Domains\Contact\DavClient\Jobs\UpdateAddressBooks;
use App\Models\AddressBookSubscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateAddressBooksTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_dispatch_subscription_update()
    {
        Queue::fake();

        $subscription = AddressBookSubscription::factory()->create();

        (new UpdateAddressBooks)->handle();

        Queue::assertPushed(SynchronizeAddressBooks::class, fn ($job) => $job->subscription->id === $subscription->id
        );
    }

    /** @test */
    public function it_does_not_dispatch_inactive_subscription()
    {
        Queue::fake();

        $subscription = AddressBookSubscription::factory()->inactive()->create();

        (new UpdateAddressBooks)->handle();

        Queue::assertNotPushed(SynchronizeAddressBooks::class, fn ($job) => $job->subscription->id === $subscription->id
        );
    }

    /** @test */
    public function it_dispatch_subscription_at_time_update()
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2020-01-01 01:01:00'));

        $subscription = AddressBookSubscription::factory([
            'last_synchronized_at' => Carbon::parse('2020-01-01 00:00:00'),
            'frequency' => 60,
        ])->create();

        (new UpdateAddressBooks)->handle();

        Queue::assertPushed(SynchronizeAddressBooks::class, fn ($job) => $job->subscription->id === $subscription->id
        );
    }

    /** @test */
    public function it_does_not_dispatch_subscription_at_time_update()
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2020-01-01 01:00:00'));

        $subscription = AddressBookSubscription::factory([
            'last_synchronized_at' => Carbon::parse('2020-01-01 00:00:00'),
            'frequency' => 60,
        ])->create();

        (new UpdateAddressBooks)->handle();

        Queue::assertNotPushed(SynchronizeAddressBooks::class, fn ($job) => $job->subscription->id === $subscription->id
        );
    }
}
