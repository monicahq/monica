<?php

namespace Tests\Unit\Controllers\Vault\ViewHelpers;

use function env;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Vault\ViewHelpers\VaultCreateViewHelper;

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
