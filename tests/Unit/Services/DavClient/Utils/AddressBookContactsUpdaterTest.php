<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use GuzzleHttp\Psr7\Response;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\AddressBookContactsUpdater;

class AddressBookContactsUpdaterTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_changes_multiget()
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

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);


        /** @var CardDAVBackend */
        $backend = $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card, $etag) {
                    $this->assertEquals($card, $cardData);
                    return true;
                })
                ->andReturn($etag);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/', new Response(200, [], $tester->multistatusHeader().
            '<d:response>'.
                '<d:href>https://test/dav/addressbooks/user@test.com/contacts/uuid</d:href>'.
                '<d:propstat>'.
                    '<d:prop>'.
                        "<d:getetag>$etag</d:getetag>".
                        "<card:address-data>$card</card:address-data>".
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
              '<d:href>https://test/dav/addressbooks/user@test.com/contacts/uuid</d:href>'.
            "</card:addressbook-multiget>\n", 'REPORT');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsUpdater())
            ->updateContacts(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/addressbooks/user@test.com/contacts/uuid' => [
                    'href' => 'https://test/dav/addressbooks/user@test.com/contacts/uuid',
                    'etag' => $etag,
                ],
            ]));

        $tester->assert();
    }

    /** @test */
    public function it_sync_changes_simple()
    {
        $subscription = AddressBookSubscription::factory()->create([
            'capabilities' => [
                'addressbookMultiget' => false,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
        ]);
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
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);


        /** @var CardDAVBackend */
        $backend = $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card, $etag) {
                    $this->assertTrue(is_resource($cardData));

                    $data = "";
                    while(! feof($cardData)) {
                        $data .= fgets($cardData);
                    }

                    fclose($cardData);

                    $this->assertEquals($card, $data);
                    return true;
                })
                ->andReturn($etag);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/addressbooks/user@test.com/contacts/uuid', new Response(200, [], $card), null, 'GET');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsUpdater())
            ->updateContacts(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/addressbooks/user@test.com/contacts/uuid' => [
                    'href' => 'https://test/dav/addressbooks/user@test.com/contacts/uuid',
                    'etag' => $etag,
                ],
            ]));

        $tester->assert();
    }
}
