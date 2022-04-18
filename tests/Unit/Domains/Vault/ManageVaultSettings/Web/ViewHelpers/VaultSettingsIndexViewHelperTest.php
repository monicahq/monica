<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Web\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use App\Models\Label;
use App\Models\Vault;
use App\Models\Template;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;

class VaultSettingsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $template = Template::factory()->create();
        $userInAccount = User::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $userInVault = User::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $vault = Vault::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $vault->users()->sync([$userInVault->id => ['permission' => 100]]);

        $array = VaultSettingsIndexViewHelper::data($vault);
        $this->assertEquals(
            7,
            count($array)
        );
        $this->assertArrayHasKey('templates', $array);
        $this->assertArrayHasKey('users_in_vault', $array);
        $this->assertArrayHasKey('users_in_account', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('labels', $array);
        $this->assertArrayHasKey('label_colors', $array);
        $this->assertEquals(
            [
                0 => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'is_default' => false,
                ],
            ],
            $array['templates']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $userInVault->id,
                    'name' => $userInVault->name,
                    'permission' => 100,
                    'url' => [
                        'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInVault->id,
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInVault->id,
                    ],
                ],
            ],
            $array['users_in_vault']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $userInAccount->id,
                    'name' => $userInAccount->name,
                    'permission' => null,
                    'url' => [
                        'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInAccount->id,
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users/'.$userInAccount->id,
                    ],
                ],
            ],
            $array['users_in_account']->toArray()
        );
        $this->assertEquals(
            [
                'template_update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/template',
                'user_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/users',
                'label_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels',
                'contact_date_important_date_type_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/contactImportantDateTypes',
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings',
                'destroy' => env('APP_URL').'/vaults/'.$vault->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $label = Label::factory()->create();
        $vault = Vault::factory()->create();
        $array = VaultSettingsIndexViewHelper::dtoLabel($vault, $label);
        $this->assertEquals(
            [
                'id' => $label->id,
                'name' => $label->name,
                'count' => null,
                'bg_color' => $label->bg_color,
                'text_color' => $label->text_color,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels/'.$label->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/labels/'.$label->id,
                ],
            ],
            $array
        );
    }
}
