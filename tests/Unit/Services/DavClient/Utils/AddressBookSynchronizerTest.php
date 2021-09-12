<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use GuzzleHttp\Psr7\Response;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\Model\SyncDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookSynchronizer;
use App\Services\DavClient\Utils\AddressBookContactsPusher;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\AddressBookContactsUpdater;

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

        $subscription = $this->getSubscription();
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

        $subscription = $this->getSubscription();
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
        $subscription = $this->getSubscription();
        $backend = new CardDAVBackend($subscription->user);

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"token"')
            ->getSyncCollection('token', '"test2"');

        $client = new DavClient([], $tester->getClient());

        $sync = new SyncDto($subscription, $client, $backend);
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) use ($sync) {
            $mock->shouldReceive('updateContacts')
                ->once()
                ->withArgs(function ($localSync, $contacts) use ($sync) {
                    $this->assertEquals($sync, $localSync);
                    $this->assertEquals('https://test/dav/addressbooks/user@test.com/contacts/uuid', $contacts->first()['href']);
                    $this->assertEquals('"test2"', $contacts->first()['etag']);

                    return true;
                })
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer())
            ->sync($sync);

        $tester->assert();
    }

    /** @test */
    public function it_forcesync_changes_added_local_contact()
    {
        $subscription = $this->getSubscription();
        $backend = new CardDAVBackend($subscription->user);

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $tester->multistatusHeader().
        '<d:response>'.
            '<d:href>https://test/dav/uuid1</d:href>'.
            '<d:propstat>'.
                '<d:prop>'.
                    "<d:getetag>$etag</d:getetag>".
                '</d:prop>'.
                '<d:status>HTTP/1.1 200 OK</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
        '<card:addressbook-query xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
          '<d:prop>'.
            '<d:getetag/>'.
          '</d:prop>'.
        "</card:addressbook-query>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        $sync = new SyncDto($subscription, $client, $backend);
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) use ($sync, $contact, $etag) {
            $mock->shouldReceive('updateMissedContacts')
                ->once()
                ->withArgs(function ($localSync, $localContacts, $distContacts) use ($sync, $contact, $etag) {
                    $this->assertEquals($sync, $localSync);
                    $this->assertEquals($contact->id, $localContacts->first()->id);
                    $this->assertEquals('https://test/dav/uuid1', $distContacts->first()['href']);
                    $this->assertEquals($etag, $distContacts->first()['etag']);

                    return true;
                });
        });
        $this->mock(AddressBookContactsPusher::class, function (MockInterface $mock) {
            $mock->shouldReceive('pushContacts')
                ->once();
        });

        (new AddressBookSynchronizer())
            ->forcesync($sync);

        $tester->assert();
    }

    private function getSubscription()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $token = factory(SyncToken::class)->create([
            'account_id' => $subscription->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->localSyncToken = $token->id;
        $subscription->save();

        return $subscription;
    }
}