<?php

namespace Tests\Unit\Controllers\Settings\Preferences\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

class PreferencesIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create([
            'name_order' => '%first_name% %last_name% (%surname%)',
        ]);
        $array = PreferencesIndexViewHelper::data($user);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'back' => env('APP_URL').'/settings',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_name_order(): void
    {
        $user = User::factory()->create([
            'name_order' => '%first_name% %last_name% (%surname%)',
        ]);
        $array = PreferencesIndexViewHelper::dtoNameOrder($user);
        $this->assertEquals(
            [
                'name_example' => 'James Bond (007)',
                'name_order' => '%first_name% %last_name% (%surname%)',
                'url' => [
                    'store' => env('APP_URL').'/settings/preferences',
                ],
            ],
            $array
        );
    }
}
