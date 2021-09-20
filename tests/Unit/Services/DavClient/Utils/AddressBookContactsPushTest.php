<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use Illuminate\Support\Str;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use GuzzleHttp\Psr7\Response;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\AddressBookContactsPush;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class AddressBookContactsPushTest extends TestCase
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
                    $this->assertEquals($uri, 'https://test/dav/uricontact2');

                    return true;
                })
                ->andReturn([
                    'carddata' => $card,
                    'etag' => $etag,
                ]);
            $mock->shouldReceive('getUuid')
                ->withArgs(function ($uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact1');

                    return true;
                })
                ->andReturn('uricontact1');
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/uricontact2', new Response(200, ['Etag' => $etag]), $card, 'PUT');

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsPush())
            ->execute(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]), [
                'added' => ['https://test/dav/uricontact2'],
            ])
            ->wait();

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
                    $this->assertStringStartsWith('https://test/dav/uricontact', $uri);

                    return true;
                })
                ->andReturnUsing(function ($uri) {
                    return Str::contains($uri, 'uricontact1') ? 'uricontact1' : 'uricontact2';
                });
            $mock->shouldReceive('getCard')
                ->withArgs(function ($name, $uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact2');

                    return true;
                })
                ->andReturn([
                    'carddata' => $card,
                    'etag' => $etag,
                ]);
        });

        $tester = (new DavTester('https://test/dav/addressbooks/user@test.com/contacts/'));
        $tester->addResponse('https://test/dav/uricontact2', new Response(200, ['Etag' => $etag]), $card, 'PUT', ['If-Match' => $etag]);

        $client = new DavClient([], $tester->getClient());

        (new AddressBookContactsPush())
            ->execute(new SyncDto($subscription, $client, $backend), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]), [
                'modified' => ['https://test/dav/uricontact2'],
            ])
            ->wait();

        $tester->assert();
    }
}
