<?php

namespace Tests\Unit\Controllers\Settings\Preferences\ViewHelpers;

use function env;
use Carbon\Carbon;
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
            'name_order' => '%first_name% %last_name% (%nickname%)',
            'date_format' => 'y',
        ]);

        $array = PreferencesIndexViewHelper::data($user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('name_order', $array);
        $this->assertArrayHasKey('date_format', $array);
        $this->assertArrayHasKey('url', $array);

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
            'name_order' => '%first_name% %last_name% (%nickname%)',
        ]);
        $array = PreferencesIndexViewHelper::dtoNameOrder($user);
        $this->assertEquals(
            [
                'name_example' => 'James Bond (007)',
                'name_order' => '%first_name% %last_name% (%nickname%)',
                'url' => [
                    'store' => env('APP_URL').'/settings/preferences/name',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_date_format(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create([
            'date_format' => 'Y',
        ]);

        $array = PreferencesIndexViewHelper::dtoDateFormat($user);
        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('dates', $array);
        $this->assertArrayHasKey('date_format', $array);
        $this->assertArrayHasKey('human_date_format', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/preferences/date',
            ],
            $array['url']
        );

        $this->assertEquals(
            'Y',
            $array['date_format']
        );

        $this->assertEquals(
            '2018',
            $array['human_date_format']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'format' => 'MMM DD, YYYY',
                    'value' => 'Jan 01, 2018',
                ],
                1 => [
                    'id' => 2,
                    'format' => 'DD MMM YYYY',
                    'value' => '01 Jan 2018',
                ],
                2 => [
                    'id' => 3,
                    'format' => 'YYYY/MM/DD',
                    'value' => '2018/01/01',
                ],
                3 => [
                    'id' => 4,
                    'format' => 'DD/MM/YYYY',
                    'value' => '01/01/2018',
                ],
            ],
            $array['dates']->toArray()
        );
    }
}
