<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use Tests\Api\DAV\CardEtag;
use App\Jobs\Dav\UpdateVCard;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use App\Models\Account\AddressBook;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Model\ContactUpdateDto;

class UpdateVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_create_a_contact()
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBook = AddressBook::factory()->create([
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

        $pendingBatch = $fake->batch([
            $job = new UpdateVCard($user, $addressBook->name, new ContactUpdateDto('https://test/dav/uricontact1', $etag, $card)),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(UpdateVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Test',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
    }
}
