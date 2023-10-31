<?php

namespace Tests\Unit\Helpers;

use App\Helpers\StorageHelper;
use App\Models\Account;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StorageHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_checks_if_we_can_upload_another_file_in_the_account(): void
    {
        $account = Account::factory()->create([
            'storage_limit_in_mb' => 1,
        ]);

        $this->assertTrue(StorageHelper::canUploadFile($account));

        $vault = Vault::factory()->create([
            'account_id' => $account->id,
        ]);
        File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 1024 * 1024,
        ]);

        $this->assertFalse(StorageHelper::canUploadFile($account));
    }

    /** @test */
    public function it_checks_if_we_can_upload_files_with_0_storage_limit(): void
    {
        $account = Account::factory()->create([
            'storage_limit_in_mb' => 0,
        ]);

        $this->assertTrue(StorageHelper::canUploadFile($account));

        $vault = Vault::factory()->create([
            'account_id' => $account->id,
        ]);
        File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 1024 * 1024,
        ]);

        $this->assertTrue(StorageHelper::canUploadFile($account));
    }
}
