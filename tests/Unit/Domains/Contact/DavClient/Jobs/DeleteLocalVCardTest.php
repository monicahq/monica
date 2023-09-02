<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Jobs\DeleteLocalVCard;
use App\Domains\Contact\ManageContact\Services\DestroyContact;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteLocalVCardTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_delete_card_mock()
    {
        $fake = Bus::fake();

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) {
            $mock->shouldReceive('withUser')->andReturnSelf();
            $mock->shouldReceive('deleteCard')->andReturnTrue();
        });

        $addressBookSubscription = AddressBookSubscription::factory()->create();

        $pendingBatch = $fake->batch([
            $job = new DeleteLocalVCard($addressBookSubscription, 'https://test/dav/uri'),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(DeleteLocalVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();
    }

    /** @test */
    public function it_delete_card_contact()
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

        (new DeleteLocalVCard($subscription, $contact->id))->handle();

        $fake->assertDispatched(function (DestroyContact $job) use ($contact) {
            $this->assertEquals($contact->id, $job->contact->id);

            return true;
        });
    }
}
