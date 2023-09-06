<?php

namespace Tests\Unit\Domains\Contact\DavClient\Jobs;

use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class PushVCardTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_create_dto_string()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $dto = new PushVCard($subscription, 'uri', 'etag', 'card', 'id');
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('id', $dto->contactId);
        $this->assertEquals('card', $dto->card);
    }

    /** @test */
    public function it_create_dto_resource()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $resource = fopen(__DIR__.'/stub.vcf', 'r');
        $dto = new PushVCard($subscription, 'uri', 'etag', $resource, 'id');
        $this->assertEquals('uri', $dto->uri);
        $this->assertEquals('etag', $dto->etag);
        $this->assertEquals('id', $dto->contactId);
        $this->assertEquals('card', $dto->card);
    }

    /**
     * @test
     *
     * @dataProvider modes
     */
    public function it_push_card($mode, $ifmatch)
    {
        $fake = Bus::fake();

        $subscription = AddressBookSubscription::factory()->create([
            'uri' => 'https://test/dav',
        ]);

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
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
            $job = new PushVCard($subscription, 'https://test/dav/uri', $etag, $card, $contact->id, $mode),
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

    public static function modes(): array
    {
        return [
            [0, []],
            [1, ['etag']],
            [2, ['*']],
        ];
    }
}
