<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Jobs\DeleteLocalVCard;
use App\Domains\Contact\DavClient\Jobs\DeleteMultipleVCard;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeleteMultipleVCardTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_delete_cards()
    {
        $fake = Bus::fake();

        $addressBookSubscription = AddressBookSubscription::factory()->create();

        $pendingBatch = $fake->batch([
            $job = new DeleteMultipleVCard($addressBookSubscription, ['https://test/dav/uri']),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(DeleteMultipleVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $fake->assertDispatched(function (DeleteLocalVCard $updateVCard) {
            $uri = $this->getPrivateValue($updateVCard, 'uri');
            $this->assertEquals('https://test/dav/uri', $uri);

            return true;
        });
    }
}
