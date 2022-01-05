<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Jobs\Dav\GetMultipleVCard;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\AddressBookContactsUpdaterMissed;

class AddressBookContactsUpdaterMissedTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_changes_missed()
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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getUuid')
                ->withArgs(function ($uri) {
                    $this->assertEquals($uri, 'https://test/dav/uuid2');

                    return true;
                })
                ->andReturn('uuid2');
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card) {
                    $this->assertEquals($card, $cardData);

                    return true;
                })
                ->andReturn($etag);
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsUpdaterMissed())
            ->execute(new SyncDto($subscription, $client), collect([
                [
                    'uuid' => 'uuid1',
                ],
            ]), collect([
                'https://test/dav/uuid2' => new ContactDto('https://test/dav/uuid2', $etag),
            ]));

        $this->assertCount(2, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(GetMultipleVCard::class, $batch);
        $hrefs = $this->getPrivateValue($batch, 'hrefs');
        $this->assertEquals(['https://test/dav/uuid2'], $hrefs);
    }
}
