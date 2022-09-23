<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaultIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_general_layout_information(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $user->vaults()->attach($vault->id, [
            'permission' => Vault::PERMISSION_EDIT,
            'contact_id' => Contact::factory()->create()->id,
        ]);

        $this->be($user);

        $array = VaultIndexViewHelper::layoutData($vault);
        $this->assertCount(3, $array);
        $this->assertEquals(
            [
                'id' => $user->id,
                'name' => $user->name,
            ],
            $array['user']
        );
        $this->assertEquals(
            [
                'id' => $vault->id,
                'name' => $vault->name,
                'permission' => [
                    'at_least_editor' => true,
                    'at_least_manager' => false,
                ],
                'url' => [
                    'dashboard' => env('APP_URL').'/vaults/'.$vault->id,
                    'contacts' => env('APP_URL').'/vaults/'.$vault->id.'/contacts',
                    'journals' => env('APP_URL').'/vaults/'.$vault->id.'/journals',
                    'groups' => env('APP_URL').'/vaults/'.$vault->id.'/groups',
                    'tasks' => env('APP_URL').'/vaults/'.$vault->id.'/tasks',
                    'files' => env('APP_URL').'/vaults/'.$vault->id.'/files',
                    'settings' => env('APP_URL').'/vaults/'.$vault->id.'/settings',
                    'search' => env('APP_URL').'/vaults/'.$vault->id.'/search',
                    'get_most_consulted_contacts' => env('APP_URL').'/vaults/'.$vault->id.'/search/user/contact/mostConsulted',
                    'search_contacts_only' => env('APP_URL').'/vaults/'.$vault->id.'/search/user/contacts',
                ],
            ],
            $array['vault']
        );
        $this->assertEquals(
            [
                'vaults' => env('APP_URL').'/vaults',
                'settings' => env('APP_URL').'/settings',
                'logout' => env('APP_URL').'/logout',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $user->vaults()->sync([$vault->id => [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]]);

        $array = VaultIndexViewHelper::data($user);

        $this->assertEquals(2, count($array));

        $this->assertEquals(
            $vault->id,
            $array['vaults']->toArray()[0]['id']
        );
        $this->assertEquals(
            $vault->name,
            $array['vaults']->toArray()[0]['name']
        );
        $this->assertEquals(
            $vault->description,
            $array['vaults']->toArray()[0]['description']
        );
        $this->assertEquals(
            $vault->description,
            $array['vaults']->toArray()[0]['description']
        );
        $this->assertEquals(
            0,
            $array['vaults']->toArray()[0]['remaining_contacts']
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
