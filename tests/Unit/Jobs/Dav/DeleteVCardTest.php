<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use App\Jobs\Dav\DeleteVCard;
use Illuminate\Bus\PendingBatch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeleteVCardTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_delete_card()
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

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
