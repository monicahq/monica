<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\PrepareJobsContactPush;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class PrepareJobsContactPushTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_push_contacts_added()
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
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($contact, $card, $etag) {
            $mock->shouldReceive('withUser')->andReturnSelf();
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
                    if ($uri !== 'uricontact2' && $uri !== 'https://test/dav/uricontact1') {
                        $this->fail("Invalid uri: $uri");

                        return false;
                    }

                    return true;
                })
                ->andReturnUsing(function ($uri) {
                    return Str::contains($uri, 'uricontact1') ? 'uricontact1' : 'uricontact2';
                });
        });

        $batchs = (new PrepareJobsContactPush)
            ->withSubscription($subscription)
            ->execute(collect([
                'added' => collect(['uricontact2']),
            ]), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $this->assertEquals('uricontact2', $batch->uri);
        $this->assertEquals(PushVCard::MODE_MATCH_NONE, $batch->mode);
    }

    /** @test */
    public function it_push_contacts_modified()
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
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($contact, $card, $etag) {
            $mock->shouldReceive('withUser')->andReturnSelf();
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

        $batchs = (new PrepareJobsContactPush)
            ->withSubscription($subscription)
            ->execute(collect([
                'modified' => collect(['uricontact2']),
            ]), collect([
                'https://test/dav/uricontact1' => new ContactDto('https://test/dav/uricontact1', $etag),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $this->assertEquals('uricontact2', $batch->uri);
        $this->assertEquals(1, $batch->mode);
    }

    /** @test */
    public function it_delete_contacts_removed()
    {
        $subscription = AddressBookSubscription::factory()->create();

        $batchs = (new PrepareJobsContactPush)
            ->withSubscription($subscription)
            ->execute(collect([
                'deleted' => collect(['uricontact2']),
            ]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(DeleteVCard::class, $batch);
        $uri = $this->getPrivateValue($batch, 'uri');
        $this->assertEquals('uricontact2', $uri);
    }
}
