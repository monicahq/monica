<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\GetEtag;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetEtagTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_etag_local_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'vcard' => 'test',
        ]);

        $etag = app(GetEtag::class)->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'vcard' => $contact,
        ]);

        $this->assertEquals('"9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08"', $etag);
    }

    /** @test */
    public function it_get_etag_distant_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'vcard' => 'test',
            'distant_etag' => '"test"',
        ]);

        $etag = app(GetEtag::class)->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'vcard' => $contact,
        ]);

        $this->assertEquals('"test"', $etag);
    }
}
