<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\DavClient\Jobs\DeleteMultipleVCard;
use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookContactsPushMissed;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookContactsUpdater;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookContactsUpdaterMissed;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookSynchronizer;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use App\Models\Vault;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\Helpers\DavTester;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class AddressBookSynchronizerTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_empty_changes()
    {
        Bus::fake();

        $this->partialMock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        $subscription = $this->getSubscription();

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken($subscription->syncToken)
            ->fake();

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();
    }

    /** @test */
    public function it_sync_no_changes()
    {
        Bus::fake();

        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        $subscription = $this->getSubscription();

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"test21"')
            ->getSyncCollection('test20')
            ->fake();

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_local_contact()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"token"')
            ->getSyncCollection('token', '"test2"')
            ->fake();

        $this->mock(AddressBookContactsUpdater::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($contacts) {
                    $this->assertEquals('https://test/dav/addressbooks/user@test.com/contacts/uuid', $contacts->first()->uri);
                    $this->assertEquals('"test2"', $contacts->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_local_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"token"')
            ->getSyncCollection('token', '"test2"')
            ->fake();

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

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

        $tester = (new DavTester($subscription->uri))
            ->getSynctoken('"token"')
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

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute();

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

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester($subscription->uri))
            ->fake()
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
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

        $this->mock(AddressBookContactsUpdaterMissed::class, function (MockInterface $mock) use ($contact, $etag) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($localContacts, $distContacts) use ($contact, $etag) {
                    $this->assertContains($contact->id, $localContacts->pluck('id'));
                    $this->assertEquals('https://test/dav/uuid1', $distContacts->first()->uri);
                    $this->assertEquals($etag, $distContacts->first()->etag);

                    return true;
                })
                ->andReturn(collect());
        });
        $this->mock(AddressBookContactsPushMissed::class, function (MockInterface $mock) {
            $mock->shouldReceive('withSubscription')->once()->andReturn($mock);
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn(collect());
        });

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute(true);

        $tester->assert();
    }

    /** @test */
    public function it_forcesync_changes_added_local_contact_batched()
    {
        Bus::fake();

        $subscription = $this->getSubscription();

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'id' => 'd403af1c-8492-4e9b-9833-cf18c795dfa9',
        ]);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester($subscription->uri))
            ->fake()
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
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

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute(true);

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

        $tester = (new DavTester($subscription->uri))
            ->fake()
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', Http::response(DavTester::multistatusHeader().
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

        (new AddressBookSynchronizer)
            ->withSubscription($subscription)
            ->execute(true);

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
        $this->setPermissionInVault($subscription->user, Vault::PERMISSION_VIEW, $subscription->vault);
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->localSyncToken = $token->id;
        $subscription->save();

        return $subscription;
    }
}
