<?php

namespace Tests\Unit\Domains\Contact\DAV\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class UpdateVCardTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_creates_a_contact()
    {
        $user = User::factory()->create();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $contact = new Contact();
        $contact->forceFill([
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'updated_at' => now(),
        ]);

        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $data = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'uri' => 'https://test/dav/uricontact1',
            'etag' => $etag,
            'card' => $card,
        ];

        (new UpdateVCard($data))->handle();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
            'vcard' => $card,
            'distant_etag' => $etag,
        ]);
    }
}
