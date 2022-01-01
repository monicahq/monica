<?php

namespace Tests\Unit\Jobs\Dav;

use Tests\TestCase;
use App\Models\User\User;
use App\Jobs\Dav\PushVCard;
use Tests\Api\DAV\CardEtag;
use App\Models\Contact\Contact;
use Illuminate\Bus\PendingBatch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\DatabaseBatchRepository;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Model\ContactPushDto;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PushVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /**
     * @test
     * @dataProvider modes
     */
    public function it_push_card($mode, $ifmatch)
    {
        $fake = Bus::fake();

        $user = factory(User::class)->create();
        $addressBookSubscription = AddressBookSubscription::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'uri' => 'https://test/dav',
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => '2021-09-01',
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        if ($ifmatch == ['etag']) {
            $ifmatch = [$etag];
        }

        Http::fake(function (Request $request, $options) use ($card, $ifmatch) {
            $this->assertEquals('https://test/dav/uri', $request->url());
            $this->assertEquals('PUT', $request->method());
            $this->assertEquals($ifmatch, $request->header('If-Match'));

            return Http::response($card, 200);
        });

        $pendingBatch = $fake->batch([
            $job = new PushVCard($addressBookSubscription, new ContactPushDto('https://test/dav/uri', $etag, $card, $contact->id, $mode)),
        ]);
        $batch = $pendingBatch->dispatch();

        $fake->assertBatched(function (PendingBatch $pendingBatch) {
            $this->assertCount(1, $pendingBatch->jobs);
            $this->assertInstanceOf(PushVCard::class, $pendingBatch->jobs->first());

            return true;
        });

        $batch = app(DatabaseBatchRepository::class)->store($pendingBatch);
        $job->withBatchId($batch->id)->handle();
    }

    public function modes(): array
    {
        return [
            [0, []],
            [1, ['etag']],
            [2, ['*']],
        ];
    }
}
