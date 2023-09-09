<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Domains\Contact\DavClient\Jobs\GetMultipleVCard;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Sabre\CardDAV\Plugin as CardDav;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class GetMultipleVCardTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_get_cards()
    {
        $fake = Bus::fake();

        $subscription = AddressBookSubscription::factory()->create();
        $this->setPermissionInVault($subscription->user, Vault::PERMISSION_EDIT, $subscription->vault);

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(DavClient::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('setBaseUri')->once()->andReturnSelf();
            $mock->shouldReceive('setCredentials')->once()->andReturnSelf();
            $mock->shouldReceive('addressbookMultiget')
                ->once()
                ->withArgs(function ($properties, $contacts) {
                    $this->assertEquals([
                        '{DAV:}getetag',
                        [
                            'name' => '{'.CardDav::NS_CARDDAV.'}address-data',
                            'value' => null,
                            'attributes' => [
                                'content-type' => 'text/vcard',
                                'version' => '4.0',
                            ],
                        ],
                    ], $properties);
                    $this->assertEquals(['https://test/dav/uri'], $contacts);

                    return true;
                })
                ->andReturn([
                    'https://test/dav/uri' => [
                        'properties' => [
                            200 => [
                                '{'.CardDav::NS_CARDDAV.'}address-data' => $card,
                                '{DAV:}getetag' => $etag,
                            ],
                        ],
                        'status' => '200',
                    ],
                ]);
        });

        $pendingBatch = $fake->batch([
            $job = new GetMultipleVCard($subscription, ['https://test/dav/uri']),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetMultipleVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $fake->assertDispatched(function (UpdateVCard $updateVCard) use ($subscription, $etag, $card) {
            $this->assertEquals([
                'account_id' => $subscription->vault->account_id,
                'author_id' => $subscription->user_id,
                'vault_id' => $subscription->vault_id,
                'uri' => 'https://test/dav/uri',
                'etag' => $etag,
                'card' => $card,
                'external' => true,
            ], $updateVCard->data);

            return true;
        });
    }

    /** @test */
    public function it_get_cards_mock_http()
    {
        $fake = Bus::fake();

        $subscription = AddressBookSubscription::factory()->create();
        $this->setPermissionInVault($subscription->user, Vault::PERMISSION_EDIT, $subscription->vault);

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(DavClient::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('setBaseUri')->once()->andReturnSelf();
            $mock->shouldReceive('setCredentials')->once()->andReturnSelf();
            $mock->shouldReceive('addressbookMultiget')
                ->once()
                ->withArgs(function ($properties, $contacts) {
                    $this->assertEquals([
                        '{DAV:}getetag',
                        [
                            'name' => '{'.CardDav::NS_CARDDAV.'}address-data',
                            'value' => null,
                            'attributes' => [
                                'content-type' => 'text/vcard',
                                'version' => '4.0',
                            ],
                        ],
                    ], $properties);
                    $this->assertEquals(['https://test/dav/uri'], $contacts);

                    return true;
                })
                ->andReturn([
                    'https://test/dav/uri' => [
                        'properties' => [
                            200 => [
                                '{'.CardDav::NS_CARDDAV.'}address-data' => $card,
                                '{DAV:}getetag' => $etag,
                            ],
                        ],
                        'status' => '200',
                    ],
                ]);
        });

        $pendingBatch = $fake->batch([
            $job = new GetMultipleVCard($subscription, ['https://test/dav/uri']),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetMultipleVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $fake->assertDispatched(function (UpdateVCard $updateVCard) use ($subscription, $etag, $card) {
            $this->assertEquals([
                'account_id' => $subscription->vault->account_id,
                'author_id' => $subscription->user_id,
                'vault_id' => $subscription->vault_id,
                'uri' => 'https://test/dav/uri',
                'etag' => $etag,
                'card' => $card,
                'external' => true,
            ], $updateVCard->data);

            return true;
        });
    }
}
