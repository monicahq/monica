<?php

namespace Tests\Unit\Services\Vault\ManageVault\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;

class VaultIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_general_layout_information(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->be($user);

        $array = VaultIndexViewHelper::layoutData($vault);
        $this->assertEquals(
            [
                'user' => [
                    'name' => $user->name,
                ],
                'vault' => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                ],
                'url' => [
                    'vaults' => env('APP_URL').'/vaults',
                    'logout' => env('APP_URL').'/logout',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();

        $array = VaultIndexViewHelper::data($vault->account);

        $this->assertEquals(2, count($array));

        $this->assertEquals(
            [
                0 => [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id,
                    ],
                ],
            ],
            $array['vaults']->toArray()
        );

        $this->assertEquals(
            [
                'vault' => [
                    'new' => env('APP_URL').'/vaults/new',
                ],
            ],
            $array['url']
        );
    }
}
