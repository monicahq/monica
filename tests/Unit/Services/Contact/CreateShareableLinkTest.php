<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Services\Contact\CreateShareableLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateShareableLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_shareable_link()
    {
        $contact = factory(Contact::class)->create([]);

        $shareableService = new CreateShareableLink;
        $link = $shareableService->execute([
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue(
            strlen($link) == 240
        );
    }
}
