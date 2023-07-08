<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookContactsUpdaterMissed;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class AddressBookContactsUpdaterMissedTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_sync_changes_missed()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->localSyncToken = $token->id;
        $subscription->save();

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('withUser')->andReturn($mock);
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

        $batchs = (new AddressBookContactsUpdaterMissed)
            ->withSubscription($subscription)
            ->execute(collect([
                [
                    'id' => 'uuid1',
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
