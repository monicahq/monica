<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use App\Jobs\Dav\GetVCard;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use App\Jobs\Dav\DeleteVCard;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Jobs\Dav\GetMultipleVCard;
use App\Jobs\Dav\DeleteMultipleVCard;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Model\ContactDeleteDto;
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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card) {
                    $this->assertEquals($card, $cardData);

                    return true;
                })
                ->andReturn($etag);
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsUpdater())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uuid2' => new ContactDto('https://test/dav/uuid2', $etag),
            ]));

        $this->assertCount(2, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(GetMultipleVCard::class, $batch);
        $hrefs = $this->getPrivateValue($batch, 'hrefs');
        $this->assertEquals(['https://test/dav/uuid2'], $hrefs);
    }

    /** @test */
    public function it_sync_deleted_multiget()
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

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsUpdater())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uuid2' => new ContactDeleteDto('https://test/dav/uuid2'),
            ]));

        $this->assertCount(2, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(GetMultipleVCard::class, $batch);
        $hrefs = $this->getPrivateValue($batch, 'hrefs');
        $this->assertEquals([], $hrefs);

        $batch = $batchs[1];
        $this->assertInstanceOf(DeleteMultipleVCard::class, $batch);
        $hrefs = $this->getPrivateValue($batch, 'hrefs');
        $this->assertEquals(['https://test/dav/uuid2'], $hrefs);
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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card) {
                    $this->assertTrue(is_resource($cardData));

                    $data = '';
                    while (! feof($cardData)) {
                        $data .= fgets($cardData);
                    }

                    fclose($cardData);

                    $this->assertEquals($card, $data);

                    return true;
                })
                ->andReturn($etag);
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsUpdater())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uuid2' => new ContactDto('https://test/dav/uuid2', $etag),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(GetVCard::class, $batch);
        $dto = $this->getPrivateValue($batch, 'contact');
        $this->assertInstanceOf(ContactDto::class, $dto);
        $this->assertEquals('https://test/dav/uuid2', $dto->uri);
    }

    /** @test */
    public function it_sync_deleted_simple()
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

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsUpdater())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uuid2' => new ContactDeleteDto('https://test/dav/uuid2'),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(DeleteVCard::class, $batch);
        $uri = $this->getPrivateValue($batch, 'uri');
        $this->assertEquals('https://test/dav/uuid2', $uri);
    }
}
