<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use Mockery\MockInterface;
use Tests\Api\DAV\CardEtag;
use App\Jobs\Dav\UpdateVCard;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use App\Jobs\Dav\GetMultipleVCard;
use Illuminate\Support\Facades\Bus;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetMultipleVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_get_cards()
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(DavClient::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('setBaseUri')->once()->andReturn($mock);
            $mock->shouldReceive('setCredentials')->once()->andReturn($mock);
            $mock->shouldReceive('addressbookMultiget')
                ->once()
                ->withArgs(function ($properties, $contacts) {
                    $this->assertEquals([
                        '{DAV:}getetag',
                        [
                            'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
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
                        200 => [
                            '{'.CardDAVPlugin::NS_CARDDAV.'}address-data' => $card,
                            '{DAV:}getetag' => $etag,
                        ],
                    ],
                ]);
        });

        $pendingBatch = $fake->batch([
            $job = new GetMultipleVCard($addressBookSubscription, ['https://test/dav/uri']),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetMultipleVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $fake->assertDispatched(function (UpdateVCard $updateVCard) use ($etag, $card) {
            $dto = $this->getPrivateValue($updateVCard, 'contact');
            $this->assertEquals('https://test/dav/uri', $dto->uri);
            $this->assertEquals($etag, $dto->etag);
            $this->assertEquals($card, $dto->card);

            return true;
        });
    }

    /** @test */
    public function it_get_cards_mock_http()
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(DavClient::class, function (MockInterface $mock) use ($card, $etag) {
            $mock->shouldReceive('setBaseUri')->once()->andReturn($mock);
            $mock->shouldReceive('setCredentials')->once()->andReturn($mock);
            $mock->shouldReceive('addressbookMultiget')
                ->once()
                ->withArgs(function ($properties, $contacts) {
                    $this->assertEquals([
                        '{DAV:}getetag',
                        [
                            'name' => '{'.CardDAVPlugin::NS_CARDDAV.'}address-data',
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
                        200 => [
                            '{'.CardDAVPlugin::NS_CARDDAV.'}address-data' => $card,
                            '{DAV:}getetag' => $etag,
                        ],
                    ],
                ]);
        });

        $pendingBatch = $fake->batch([
            $job = new GetMultipleVCard($addressBookSubscription, ['https://test/dav/uri']),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetMultipleVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $fake->assertDispatched(function (UpdateVCard $updateVCard) use ($etag, $card) {
            $dto = $this->getPrivateValue($updateVCard, 'contact');
            $this->assertEquals('https://test/dav/uri', $dto->uri);
            $this->assertEquals($etag, $dto->etag);
            $this->assertEquals($card, $dto->card);

            return true;
        });
    }
}
