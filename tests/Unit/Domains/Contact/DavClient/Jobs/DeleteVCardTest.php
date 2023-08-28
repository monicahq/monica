<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Models\AddressBookSubscription;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeleteVCardTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_delete_card()
    {
        $fake = Bus::fake();

        $addressBookSubscription = AddressBookSubscription::factory()->create();

        Http::fake(function (Request $request) {
            $this->assertEquals('https://test/dav/uri', $request->url());
            $this->assertEquals('DELETE', $request->method());

            return Http::response(null, 204);
        });

        $pendingBatch = $fake->batch([
            $job = new DeleteVCard($addressBookSubscription, 'https://test/dav/uri'),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(DeleteVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();
    }
}
