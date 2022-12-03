<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Web\ViewHelpers;

use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Models\Contact;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
        $vault->users()->sync([$userInVault->id => ['permission' => 100, 'contact_id' => Contact::factory()->create()->id]]);

        $vault->refresh();
        $array = VaultSettingsIndexViewHelper::data($vault);
        $this->assertCount(
            8,
            $array
        );
        $this->assertArrayHasKey('templates', $array);
        $this->assertArrayHasKey('users_in_vault', $array);
        $this->assertArrayHasKey('users_in_account', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('labels', $array);
        $this->assertArrayHasKey('label_colors', $array);
        $this->assertArrayHasKey('tags', $array);
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
                'tag_store' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags',
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
        $vault = Vault::factory()->create();
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $array = VaultSettingsIndexViewHelper::dtoLabel($label);
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

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object_tag(): void
    {
        $vault = Vault::factory()->create();
        $tag = Tag::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $array = VaultSettingsIndexViewHelper::dtoTag($tag);
        $this->assertEquals(
            [
                'id' => $tag->id,
                'name' => $tag->name,
                'count' => null,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags/'.$tag->id,
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/settings/tags/'.$tag->id,
                ],
            ],
            $array
        );
    }
}
