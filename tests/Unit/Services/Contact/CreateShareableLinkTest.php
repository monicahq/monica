<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Services\Contact\CreateShareableLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateShareableLinkTest extends TestCase
{
    //use DatabaseTransactions;

    public function test_it_creates_a_shareable_link()
    {
        $contact = factory(Contact::class)->create([]);
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'share_expire_at' => null,
            'shareable_link' => null,
        ]);

        $shareableService = new CreateShareableLink;
        $link = $shareableService->execute([
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue(
            strlen($link) == 240
        );

        $contact = Contact::find($contact->id);

        $this->assertEquals(
            '2017-01-04',
            $contact->share_expire_at->format('Y-m-d')
        );

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'share_expire_at' => null,
            'shareable_link' => null,
        ]);
    }
}
