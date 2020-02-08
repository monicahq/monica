<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Helpers\StorageHelper;
use App\Models\Account\Account;
use App\Models\Contact\Document;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StorageHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_calculates_the_account_storage_size(): void
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

    /** @test */
    public function it_tests_account_storage_limit(): void
    {
        config(['monica.requires_subscription' => true]);
        $account = factory(Account::class)->create([]);

        factory(Document::class)->create([
            'filesize' => 1000000,
            'account_id' => $account->id,
        ]);

        config(['monica.max_storage_size' => 0.1]);
        $this->assertTrue(StorageHelper::hasReachedAccountStorageLimit($account));

        config(['monica.max_storage_size' => 1]);
        $this->assertFalse(StorageHelper::hasReachedAccountStorageLimit($account));

        factory(Photo::class)->create([
            'filesize' => 1000000,
            'account_id' => $account->id,
        ]);

        config(['monica.max_storage_size' => 2]);
        $this->assertFalse(StorageHelper::hasReachedAccountStorageLimit($account));

        config(['monica.max_storage_size' => 1]);
        $this->assertTrue(StorageHelper::hasReachedAccountStorageLimit($account));

        config(['monica.requires_subscription' => false]);
        $this->assertFalse(StorageHelper::hasReachedAccountStorageLimit($account));
    }
}
