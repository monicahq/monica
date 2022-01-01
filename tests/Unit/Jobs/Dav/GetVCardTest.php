<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use App\Jobs\Dav\GetVCard;
use Tests\Api\DAV\CardEtag;
use App\Jobs\Dav\UpdateVCard;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\ContactDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_get_card()
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

        Http::fake([
            'https://test/dav/uri' => Http::response($card, 200),
        ]);

        $pendingBatch = $fake->batch([
            $job = new GetVCard($addressBookSubscription, new ContactDto('https://test/dav/uri', $etag)),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(GetVCard::class, $pendingBatch->jobs->first());

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
