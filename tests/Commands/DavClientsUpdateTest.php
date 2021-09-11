<?php

namespace Tests\Commands;

use App\Jobs\SynchronizeAddressBooks;
use App\Models\Account\AddressBookSubscription;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class DavClientsUpdateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_dispatch_subscription_update()
    {
        Queue::fake();

        $subscription = AddressBookSubscription::factory()->create();

        Artisan::call('monica:davclients', []);

        Queue::assertPushed(SynchronizeAddressBooks::class, function ($job) use ($subscription) {
            return $job->subscription->id === $subscription->id;
        });
    }
}
