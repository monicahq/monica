<?php

namespace Tests\Unit\Domains\Contact\DAV\Jobs;

use App\Domains\Contact\Dav\Jobs\UpdateVCard;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class UpdateVCardTest extends TestCase
{
    use CardEtag;
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_contact()
    {
        $user = User::factory()->create();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $card = 'BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
UID:affacde9-b2fe-4371-9acb-6612aaee6971
SOURCE:
FN:Test
N:;Test;;;
REV:20230715T112647Z
END:VCARD';
        $etag = '"'.hash('sha256', $card).'"';

        $data = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'uri' => 'https://test/dav/affacde9-b2fe-4371-9acb-6612aaee6971',
            'etag' => $etag,
            'card' => $card,
            'external' => true,
        ];

        (new UpdateVCard($data))->handle();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Test',
            'vcard' => $card,
            'distant_etag' => $etag,
            'distant_uuid' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
    }
}
