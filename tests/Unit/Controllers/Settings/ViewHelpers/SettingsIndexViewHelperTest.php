<?php

namespace Tests\Unit\Controllers\Settings\Users\ViewHelpers;

use function env;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\ViewHelpers\SettingsIndexViewHelper;

class SettingsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $array = SettingsIndexViewHelper::data();
        $this->assertEquals(
            [
                'url' => [
                    'users' => [
                        'index' => env('APP_URL').'/settings/users',
                    ],
                ],
            ],
            $array
        );
    }
}
