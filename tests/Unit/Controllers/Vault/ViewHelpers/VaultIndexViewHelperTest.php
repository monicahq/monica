<?php

namespace Tests\Unit\Controllers\Vault\ViewHelpers;

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
                    'url' => [
                        'dashboard' => env('APP_URL').'/vaults/'.$vault->id,
                        'contacts' => env('APP_URL').'/vaults/'.$vault->id.'/contacts',
                    ],
                ],
                'url' => [
                    'vaults' => env('APP_URL').'/vaults',
                    'settings' => env('APP_URL').'/settings',
                    'logout' => env('APP_URL').'/logout',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $user->vaults()->sync([$vault->id => ['permission' => Vault::PERMISSION_MANAGE]]);

        $array = VaultIndexViewHelper::data($user);

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
                    'create' => env('APP_URL').'/vaults/create',
                ],
            ],
            $array['url']
        );
    }
}
