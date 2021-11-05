<?php

namespace Tests\Unit\Services\VCard;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\VCard\GetEtag;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetEtagTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_etag_local_contact()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'vcard' => 'test',
        ]);

        $etag = app(GetEtag::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertEquals('"a94a8fe5ccb19ba61c4c0873d391e987982fbbd3"', $etag);
    }

    /** @test */
    public function it_get_etag_distant_contact()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'vcard' => 'test',
            'distant_etag' => '"test"',
        ]);

        $etag = app(GetEtag::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertEquals('"test"', $etag);
    }
}
