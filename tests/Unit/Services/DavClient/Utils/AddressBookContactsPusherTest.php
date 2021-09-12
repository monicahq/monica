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
use App\Services\DavClient\Utils\AddressBookContactsPusher;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class AddressBookContactsPusherTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_push_contacts_added()
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

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        /** @var CardDAVBackend */
        $backend = $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('getCard')
                ->withArgs(function ($name, $uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact');

                    return true;
                })
                ->andReturn([
                    'carddata' => $card,
                    'etag' => $etag,
                ]);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/uricontact', new Response(200, ['Etag' => $etag]), $card, 'PUT');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsPusher())
            ->pushContacts(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/addressbooks/user@test.com/contacts/uuid' => [
                    'href' => 'https://test/dav/addressbooks/user@test.com/contacts/uuid',
                    'etag' => $etag,
                ],
            ]), [
                'added' => ['https://test/dav/uricontact'],
            ]);

        $tester->assert();
    }

    /** @test */
    public function it_push_contacts_modified()
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

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        /** @var CardDAVBackend */
        $backend = $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('getUuid')
                ->withArgs(function ($uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact');

                    return true;
                })
                ->andReturn('uricontact');
            $mock->shouldReceive('getCard')
                ->withArgs(function ($name, $uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact');

                    return true;
                })
                ->andReturn([
                    'carddata' => $card,
                    'etag' => $etag,
                ]);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/uricontact', new Response(200, ['Etag' => $etag]), $card, 'PUT', ['If-Match' => $etag]);

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsPusher())
            ->pushContacts(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/addressbooks/user@test.com/contacts/uuid' => [
                    'href' => 'https://test/dav/addressbooks/user@test.com/contacts/uuid',
                    'etag' => $etag,
                ],
            ]), [
                'modified' => ['https://test/dav/uricontact'],
            ]);

        $tester->assert();
    }

    /** @test */
    public function it_push_contacts_missed()
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

        $contact = factory(Contact::class)->create([
            'account_id' => $subscription->account_id,
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        /** @var CardDAVBackend */
        $backend = $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag, $contact) {
            $mock->shouldReceive('getUuid')
                ->once()
                ->withArgs(function ($uri) {
                    $this->assertEquals('https://test/dav/uuid6', $uri);

                    return true;
                })
                ->andReturn('uuid3');
            $mock->shouldReceive('prepareCard')
                ->once()
                ->withArgs(function ($c) use ($contact) {
                    $this->assertEquals($contact, $c);

                    return true;
                })
                ->andReturn([
                    'carddata' => $card,
                    'uri' => 'https://test/dav/uuid3',
                    'etag' => $etag,
                ]);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/uuid3', new Response(200, ['Etag' => $etag]), $card, 'PUT', ['If-Match' => '*']);

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsPusher())
            ->pushContacts(new SyncDto($subscription, $client, $backend), collect(), [], collect([
                [
                    'href' => 'https://test/dav/uuid6',
                    'etag' => $etag,
                ],
            ]), collect([$contact]));

        $tester->assert();
    }
}
