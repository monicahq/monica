<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultCreateViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class VaultCreateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $array = VaultCreateViewHelper::data();
        $this->assertEquals(
            [
                'url' => [
                    'store' => env('APP_URL').'/vaults',
                    'back' => env('APP_URL').'/vaults',
                ],
            ],
            $array
        );
    }
}
