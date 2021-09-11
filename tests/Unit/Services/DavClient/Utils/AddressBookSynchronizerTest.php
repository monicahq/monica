<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use GuzzleHttp\Psr7\Response;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookSynchronizer;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\AddressBookContactsUpdater;
use App\Services\DavClient\Utils\Model\SyncDto;
use Mockery\MockInterface;

class AddressBookSynchronizerTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_empty_changes()
    {
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('updateContacts')
                ->once()
                ->andReturn(collect());
        });

        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
            ->getSynctoken($subscription->syncToken);
        $client = new DavClient([], $tester->getClient());

        (new AddressBookSynchronizer())
            ->sync(new SyncDto($subscription, $client, $backend));

        $tester->assert();
    }

    /** @test */
    public function it_sync_no_changes()
    {
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('updateContacts')
                ->once()
                ->andReturn(collect());
        });

        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test2"')
            ->getSyncCollection('test2');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookSynchronizer())
            ->sync(new SyncDto($subscription, $client, $backend));

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_local_contact()
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

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test2"')
            ->getSyncCollection('token', '"test2"');

        $client = new DavClient([], $tester->getClient());

        $sync = new SyncDto($subscription, $client, $backend);
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) use ($sync) {
            $mock->shouldReceive('updateContacts')
                ->once()
                ->withArgs(function ($localSync, $contacts) use ($sync) {
                    return $sync === $localSync
                    && $contacts->first()['href'] === 'https://test/dav/addressbooks/user@test.com/contacts/uuid'
                    && $contacts->first()['etag'] === '"test2"';
                })
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer())
            ->sync($sync);

        $tester->assert();
    }
}
