<?php

namespace Tests\Unit\Controllers\Vault\Settings\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\Vault;
use App\Models\Template;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Vault\Settings\ViewHelpers\VaultSettingsIndexViewHelper;

class VaultSettingsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $template = Template::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $template->account_id,
        ]);

        $array = VaultSettingsIndexViewHelper::data($vault);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('templates', $array);
        $this->assertArrayHasKey('url', $array);
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
                'template_update' => env('APP_URL').'/vaults/'.$vault->id.'/settings/template',
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/settings',
                'destroy' => env('APP_URL').'/vaults/'.$vault->id,
            ],
            $array['url']
        );
    }
}
