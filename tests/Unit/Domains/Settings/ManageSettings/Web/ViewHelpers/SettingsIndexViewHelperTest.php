<?php

namespace Tests\Unit\Domains\Settings\ManageSettings\Web\ViewHelpers;

use App\Domains\Settings\ManageSettings\Web\ViewHelpers\SettingsIndexViewHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class SettingsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create([
            'is_account_administrator' => true,
        ]);
        $array = SettingsIndexViewHelper::data($user);
        $this->assertEquals(
            [
                'is_account_administrator' => true,
                'url' => [
                    'preferences' => [
                        'index' => env('APP_URL').'/settings/preferences',
                    ],
                    'notifications' => [
                        'index' => env('APP_URL').'/settings/notifications',
                    ],
                    'users' => [
                        'index' => env('APP_URL').'/settings/users',
                    ],
                    'personalize' => [
                        'index' => env('APP_URL').'/settings/personalize',
                    ],
                    'storage' => [
                        'index' => env('APP_URL').'/settings/storage',
                    ],
                    'cancel' => [
                        'index' => env('APP_URL').'/settings/cancel',
                    ],
                ],
            ],
            $array
        );
    }
}
