<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Mockery\MockInterface;
use App\Jobs\Dav\PushVCard;
use Illuminate\Support\Str;
use Tests\Api\DAV\CardEtag;
use Tests\Helpers\DavTester;
use App\Jobs\Dav\DeleteVCard;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Model\ContactPushDto;
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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($contact, $card, $etag) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getCard')
                ->withArgs(function ($name, $uri) {
                    $this->assertEquals($uri, 'uricontact2');

                    return true;
                })
                ->andReturn([
                    'contact_id' => $contact->id,
                    'carddata' => $card,
                    'etag' => $etag,
                    'distant_etag' => $etag,
                ]);
            $mock->shouldReceive('getUuid')
                ->withArgs(function ($uri) {
                    $this->assertEquals($uri, 'https://test/dav/uricontact1');

                    return true;
                })
                ->andReturn('uricontact1');
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsPush())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]), [
                'added' => ['uricontact2'],
            ]);

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $dto = $this->getPrivateValue($batch, 'contact');
        $this->assertInstanceOf(ContactPushDto::class, $dto);
        $this->assertEquals('uricontact2', $dto->uri);
        $this->assertEquals(ContactPushDto::MODE_MATCH_NONE, $dto->mode);
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

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($contact, $card, $etag) {
            $mock->shouldReceive('init')->andReturn($mock);
            $mock->shouldReceive('getUuid')
                ->withArgs(function ($uri) {
                    $this->assertStringContainsString('uricontact', $uri);

                    return true;
                })
                ->andReturnUsing(function ($uri) {
                    return Str::contains($uri, 'uricontact1') ? 'uricontact1' : 'uricontact2';
                });
            $mock->shouldReceive('getCard')
                ->withArgs(function ($name, $uri) {
                    $this->assertEquals($uri, 'uricontact2');

                    return true;
                })
                ->andReturn([
                    'contact_id' => $contact->id,
                    'carddata' => $card,
                    'etag' => $etag,
                    'distant_etag' => $etag,
                ]);
        });

        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsPush())
            ->execute(new SyncDto($subscription, $client), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]), [
                'modified' => ['uricontact2'],
            ]);

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $dto = $this->getPrivateValue($batch, 'contact');
        $this->assertInstanceOf(ContactPushDto::class, $dto);
        $this->assertEquals('uricontact2', $dto->uri);
        $this->assertEquals(1, $dto->mode);
    }

    /** @test */
    public function it_delete_contacts_removed()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $client = (new DavTester())->fake()->client();

        $batchs = (new AddressBookContactsPush())
            ->execute(new SyncDto($subscription, $client), collect(), [
                'deleted' => ['uricontact2'],
            ]);

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(DeleteVCard::class, $batch);
        $uri = $this->getPrivateValue($batch, 'uri');
        $this->assertEquals('uricontact2', $uri);
    }
}
