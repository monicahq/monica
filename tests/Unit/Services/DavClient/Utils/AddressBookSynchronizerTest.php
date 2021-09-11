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

class AddressBookSynchronizerTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_empty_changes()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $backend = new CardDAVBackend($subscription->user);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'))
            ->getSynctoken($subscription->syncToken);
        $client = new DavClient([], $tester->getClient());

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

        $client = new DavClient([], $tester->getClient());

        $result = (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

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
            ->getSyncCollection('"test2"')
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $tester->multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        '<d:getetag></d:getetag>'.
                        '<card:address-data></card:address-data>'.
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<card:addressbook-multiget xmlns:card="urn:ietf:params:xml:ns:carddav" xmlns:d="DAV:">'.
              '<d:prop>'.
                '<d:getetag/>'.
                '<card:address-data content-type="text/vcard" version="4.0"/>'.
              '</d:prop>'.
              '<d:href>href</d:href>'.
            "</card:addressbook-multiget>\n", 'REPORT')
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/d403af1c-8492-4e9b-9833-cf18c795dfa9.vcf',
                new Response(201, ['Etag' => $this->getEtag($contact, true)]),
                $this->getCard($contact, true), 'PUT');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_added_distant_contact()
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

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact, true);
        $etag = $this->getEtag($contact, true);

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->getSynctoken('"test2"')
            ->getSyncCollection('"test3"')
            ->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $tester->multistatusHeader().
            '<d:response>'.
                '<d:href>href</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        "<d:getetag>$etag</d:getetag>".
                        "<card:address-data>$card</card:address-data>".
                    '</d:prop>'.
                    '<d:status>HTTP/1.1 200 OK</d:status>'.
                '</d:propstat>'.
            '</d:response>'.
            '</d:multistatus>'), null, 'REPORT');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookSynchronizer($subscription, $client, $backend))
            ->sync();

        $tester->assert();
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
    }
}
