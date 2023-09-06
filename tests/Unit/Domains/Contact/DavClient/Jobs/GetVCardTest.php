<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Domains\Contact\DavClient\Jobs\GetVCard;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class GetVCardTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_get_card()
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

        Http::fake([
            'https://test/dav/uri' => Http::response($card, 200),
        ]);

        $pendingBatch = $fake->batch([
            $job = new GetVCard($subscription, new ContactDto('https://test/dav/uri', $etag)),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetVCard::class, $pendingBatch->jobs->first());

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
