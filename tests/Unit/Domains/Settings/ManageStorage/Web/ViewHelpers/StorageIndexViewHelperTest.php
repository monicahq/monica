<?php

namespace Tests\Unit\Domains\Settings\ManageStorage\Web\ViewHelpers;

use App\Domains\Settings\ManageStorage\Web\ViewHelpers\StorageIndexViewHelper;
use App\Models\Account;
use App\Models\File;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class StorageIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $account = Account::factory()->create([
            'storage_limit_in_mb' => 1,
        ]);
        $vault = Vault::factory()->create([
            'account_id' => $account->id,
        ]);
        File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 1024 * 1024,
            'type' => File::TYPE_AVATAR,
        ]);
        File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 1024 * 1024,
            'type' => File::TYPE_DOCUMENT,
        ]);
        File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 1024 * 1024,
            'type' => File::TYPE_PHOTO,
        ]);

        $this->assertEquals(
            [
                'statistics' => [
                    'total' => '3MB',
                    'total_percent' => 300,
                    'photo' => [
                        'total' => 1,
                        'total_percent' => 33,
                        'size' => '1MB',
                    ],
                    'document' => [
                        'total' => 1,
                        'total_percent' => 33,
                        'size' => '1MB',
                    ],
                    'avatar' => [
                        'total' => 1,
                        'total_percent' => 33,
                        'size' => '1MB',
                    ],
                ],
                'account_limit' => '1MB',
                'url' => [
                    'settings' => [
                        'index' => env('APP_URL').'/settings',
                    ],
                ],
            ],
            StorageIndexViewHelper::data($account)
        );
    }
}
