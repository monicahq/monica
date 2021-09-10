<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Tests\Helpers\DavTester;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Services\DavClient\Utils\Dav\Client;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookSynchronizer;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class AddressBookSynchronizerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sync_empty_changes()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
            ->getSynctoken($subscription->syncToken);
        $client = new Client([], $tester->getClient());

        $result = (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

        $tester->assert();
    }

    /** @test */
    public function it_sync_no_changes()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test2"')
            ->getSyncCollection('test2');

        $client = new Client([], $tester->getClient());

        $result = (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);
        $token = factory(SyncToken::class)->create([
            'account_id' => $subscription->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->localSyncToken = $token->id;
        $subscription->save();

        factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
        ]);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test2"')
            ->getSyncCollection('test2');

        $client = new Client([], $tester->getClient());

        $result = (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

        $tester->assert();
    }
}
