<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use App\Jobs\Dav\DeleteVCard;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Dav\DeleteMultipleVCard;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DeleteMultipleVCardTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_delete_cards()
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

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

        $fake->assertDispatched(function (DeleteVCard $updateVCard) {
            $uri = $this->getPrivateValue($updateVCard, 'uri');
            $this->assertEquals('https://test/dav/uri', $uri);

            return true;
        });
    }
}
