<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Jobs\DeleteLocalVCard;
use App\Domains\Contact\DavClient\Jobs\DeleteMultipleVCard;
use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Jobs\GetVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDeleteDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\PrepareJobsContactUpdater;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class PrepareJobsContactUpdaterTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_sync_changes_multiget()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->sync_token_id = $token->id;
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
            $mock->shouldReceive('withUser')->andReturnSelf();
            $mock->shouldReceive('updateCard')
                ->withArgs(function ($addressBookId, $cardUri, $cardData) use ($card) {
                    $this->assertEquals($card, $cardData);

                    return true;
                })
                ->andReturn($etag);
        });

        $batchs = (new PrepareJobsContactUpdater)
            ->withSubscription($subscription)
            ->execute(collect([
                'https://test/dav/uuid2' => new ContactDto('https://test/dav/uuid2', $etag),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(GetMultipleVCard::class, $batch);
        $hrefs = $this->getPrivateValue($batch, 'hrefs');
        $this->assertEquals(['https://test/dav/uuid2'], $hrefs);
    }

    /** @test */
    public function it_sync_deleted_multiget()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->sync_token_id = $token->id;
        $subscription->save();

        $batchs = (new PrepareJobsContactUpdater)
            ->withSubscription($subscription)
            ->execute(collect([
                'https://test/dav/uuid2' => new ContactDeleteDto('https://test/dav/uuid2'),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
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
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->sync_token_id = $token->id;
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
            $mock->shouldReceive('withUser')->andReturnSelf();
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

        $batchs = (new PrepareJobsContactUpdater)
            ->withSubscription($subscription)
            ->execute(collect([
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
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->sync_token_id = $token->id;
        $subscription->save();

        $batchs = (new PrepareJobsContactUpdater)
            ->withSubscription($subscription)
            ->execute(collect([
                'https://test/dav/uuid2' => new ContactDeleteDto('https://test/dav/uuid2'),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(DeleteLocalVCard::class, $batch);
        $uri = $this->getPrivateValue($batch, 'uri');
        $this->assertEquals('https://test/dav/uuid2', $uri);
    }
}
