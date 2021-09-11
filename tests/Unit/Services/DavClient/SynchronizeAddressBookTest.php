<?php

namespace Tests\Unit\Services\DavClient;

use Tests\TestCase;
use GuzzleHttp\Client;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\SynchronizeAddressBook;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookSynchronizer;

class SynchronizeAddressBookTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_runs_sync()
    {
        $spy = $this->spy(AddressBookSynchronizer::class);
        $client = new Client();

        $subscription = AddressBookSubscription::factory()->create();

        $request = [
            'account_id' => $subscription->account_id,
            'user_id' => $subscription->user_id,
            'addressbook_subscription_id' => $subscription->id,
        ];

        (new SynchronizeAddressBook())->execute($request, $client);

        $spy->shouldHaveReceived('sync');
    }

    /** @test */
    public function it_runs_sync_force()
    {
        $spy = $this->spy(AddressBookSynchronizer::class);
        $client = new Client();

        $subscription = AddressBookSubscription::factory()->create();

        $request = [
            'account_id' => $subscription->account_id,
            'user_id' => $subscription->user_id,
            'addressbook_subscription_id' => $subscription->id,
            'force' => true,
        ];

        (new SynchronizeAddressBook())->execute($request, $client);

        $spy->shouldHaveReceived('forcesync');
    }
}
