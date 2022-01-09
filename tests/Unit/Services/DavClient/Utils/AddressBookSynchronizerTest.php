<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use App\Jobs\Dav\GetMultipleVCard;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use App\Jobs\Dav\DeleteMultipleVCard;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\SyncDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookSynchronizer;
use App\Services\DavClient\Utils\AddressBookContactsUpdater;
use App\Services\DavClient\Utils\AddressBookContactsPushMissed;
use App\Services\DavClient\Utils\AddressBookContactsUpdaterMissed;

class AddressBookSynchronizerTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_empty_changes()
    {
        Bus::fake();

        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        $subscription = $this->getSubscription();

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
            ->getSynctoken($subscription->syncToken)
            ->fake();
        $client = $tester->client();

        (new AddressBookSynchronizer())
            ->execute(new SyncDto($subscription, $client));

        $tester->assert();
    }

    /** @test */
    public function it_sync_no_changes()
    {
        Bus::fake();

        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        $subscription = $this->getSubscription();

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test21"')
            ->getSyncCollection('test20')
            ->fake();

        $client = $tester->client();

        (new AddressBookSynchronizer())
            ->execute(new SyncDto($subscription, $client));

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_local_contact()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"token"')
            ->getSyncCollection('token', '"test2"')
            ->fake();

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);
        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) use ($sync) {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($localSync, $contacts) use ($sync) {
                    $this->assertEquals($sync, $localSync);
                    $this->assertEquals('https://test/dav/addressbooks/user@test.com/contacts/uuid', $contacts->first()->uri);
                    $this->assertEquals('"test2"', $contacts->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer())
            ->execute($sync);

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_local_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"token"')
            ->getSyncCollection('token', '"test2"')
            ->fake();

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);

        (new AddressBookSynchronizer())
            ->execute($sync);

        $tester->assert();

        Bus::assertBatched(function (PendingBatch $batch) {
            $this->assertCount(2, $batch->jobs);
            $job = $batch->jobs[0];
            $this->assertInstanceOf(GetMultipleVCard::class, $job);
            $this->assertEquals(['https://test/dav/addressbooks/user@test.com/contacts/uuid'], $this->getPrivateValue($job, 'hrefs'));

            return true;
        });
    }

    /** @test */
    public function it_sync_changes_deleted_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"token"')
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
            '<d:response>'.
                '<d:status>HTTP/1.1 404 Not Found</d:status>'.
                '<d:href>https://test/dav/addressbooks/user@test.com/contacts/uuid</d:href>'.
                '<d:propstat>'.
                    '<d:prop/>'.
                    '<d:status>HTTP/1.1 418 I\'m a teapot</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '<d:sync-token>token</d:sync-token>'.
            '</d:multistatus>'), null, 'REPORT')
            ->fake();

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);

        (new AddressBookSynchronizer())
            ->execute($sync);

        $tester->assert();

        Bus::assertBatched(function (PendingBatch $batch) {
            $this->assertCount(2, $batch->jobs);
            $job = $batch->jobs[1];
            $this->assertInstanceOf(DeleteMultipleVCard::class, $job);
            $this->assertEquals(['https://test/dav/addressbooks/user@test.com/contacts/uuid'], $this->getPrivateValue($job, 'hrefs'));

            return true;
        });
    }

    /** @test */
    public function it_forcesync_changes_added_local_contact()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
        ->fake();
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
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

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);
        $this->mock(AddressBookContactsUpdaterMissed::class, function (MockInterface $mock) use ($sync, $contact, $etag) {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($localSync, $localContacts, $distContacts) use ($sync, $contact, $etag) {
                    $this->assertEquals($sync, $localSync);
                    $this->assertEquals($contact->id, $localContacts->first()->id);
                    $this->assertEquals('https://test/dav/uuid1', $distContacts->first()->uri);
                    $this->assertEquals($etag, $distContacts->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });
        $this->mock(AddressBookContactsPushMissed::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer())
            ->execute($sync, true);

        $tester->assert();
    }

    /** @test */
    public function it_forcesync_changes_added_local_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'address_book_id' => $subscription->address_book_id,
            'uuid' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
        ->fake();
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
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

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);

        (new AddressBookSynchronizer())
            ->execute($sync, true);

        $tester->assert();

        Bus::assertBatched(function (PendingBatch $batch) {
            $this->assertCount(2, $batch->jobs);
            $job = $batch->jobs[0];
            $this->assertInstanceOf(GetMultipleVCard::class, $job);
            $this->assertEquals(['https://test/dav/uuid1'], $this->getPrivateValue($job, 'hrefs'));

            return true;
        });
    }

    /** @test */
    public function it_forcesync_changes_deleted_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
        ->fake();
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
        '<d:response>'.
            '<d:status>HTTP/1.1 404 Not Found</d:status>'.
            '<d:href>https://test/dav/uuid1</d:href>'.
            '<d:propstat>'.
                '<d:prop/>'.
                '<d:status>HTTP/1.1 418 I\'m a teapot</d:status>'.
            '</d:propstat>'.
        '</d:response>'.
        '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
        '<card:addressbook-query xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
          '<d:prop>'.
            '<d:getetag/>'.
          '</d:prop>'.
        "</card:addressbook-query>\n", 'REPORT');

        $client = $tester->client();

        $sync = new SyncDto($subscription, $client);

        (new AddressBookSynchronizer())
            ->execute($sync, true);

        $tester->assert();

        Bus::assertBatched(function (PendingBatch $batch) {
            $this->assertCount(2, $batch->jobs);
            $job = $batch->jobs[1];
            $this->assertInstanceOf(DeleteMultipleVCard::class, $job);
            $this->assertEquals(['https://test/dav/uuid1'], $this->getPrivateValue($job, 'hrefs'));

            return true;
        });
    }

    private function getSubscription()
    {
        $subscription = AddressBookSubscription::factory()->create([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
        ]);
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
