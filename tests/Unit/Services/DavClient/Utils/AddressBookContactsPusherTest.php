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
    public function it_push_contacts()
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
}
