<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Jobs\Dav\PushVCard;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Model\ContactPushDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Services\DavClient\Utils\AddressBookContactsPushMissed;

class AddressBookContactsPushMissedTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag, $contact) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getUuid')
                ->once()
                ->withArgs(function ($uri) {
                    $this->assertEquals('uuid6', $uri);

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
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'carddata' => $card,
                    'uri' => 'uuid3',
                    'etag' => $etag,
                ]);
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsPushMissed())
            ->execute(new SyncDto($subscription, $client), [], collect([
                'uuid6' => new ContactDto('uuid6', $etag),
            ]), collect([$contact]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $dto = $this->getPrivateValue($batch, 'contact');
        $this->assertInstanceOf(ContactPushDto::class, $dto);
        $this->assertEquals('uuid3', $dto->uri);
        $this->assertEquals(2, $dto->mode);
    }
}
