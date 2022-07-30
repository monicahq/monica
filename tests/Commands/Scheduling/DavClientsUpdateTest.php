<?php

namespace Tests\Commands\Scheduling;

use Tests\TestCase;
use App\Jobs\SynchronizeAddressBooks;
use Illuminate\Support\Facades\Queue;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DavClientsUpdateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_dispatch_subscription_update()
    {
        Queue::fake();

        $subscription = AddressBookSubscription::factory()->create();

        $this->artisan('monica:davclients')->run();

        Queue::assertPushed(SynchronizeAddressBooks::class, function ($job) use ($subscription) {
            return $job->subscription->id === $subscription->id;
        });
    }
}
