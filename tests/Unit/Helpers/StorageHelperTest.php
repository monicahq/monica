<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use function Safe\json_decode;
use App\Helpers\AccountHelper;
use App\Helpers\StorageHelper;
use App\Models\Account\Account;
use App\Models\Account\Invitation;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StorageHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_calculates_the_account_storage_size()
    {
        $account = factory(Account::class)->create([]);

        factory(Document::class)->create([
            'filesize' => 1000000,
            'account_id' => $account->id,
        ]);

        factory(Photo::class)->create([
            'filesize' => 1000000,
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            2000000,
            StorageHelper::getAccountStorageSize($account)
        );
    }
}
