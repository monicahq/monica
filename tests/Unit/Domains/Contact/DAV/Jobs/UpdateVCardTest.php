<?php

namespace Tests\Unit\Domains\Contact\DAV\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class UpdateVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_creates_a_contact()
    {
        $fake = Bus::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);

        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $pendingBatch = $fake->batch([
            $job = new UpdateVCard([
                'account_id' => $account->id,
                'author_id' => $user->id,
                'vault_id' => $vault->id,
                'uri' => 'https://test/dav/uricontact1',
                'etag' => $etag,
                'card' => $card,
            ]),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(UpdateVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
        ]);

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'vcard' => $card,
            'distant_etag' => $etag,
        ]);
    }
}
