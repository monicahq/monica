<?php

namespace Tests\Unit\Domains\Settings\ManageUserPreferences\Web\ViewHelpers;

use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class UserPreferencesIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create([
            'name_order' => '%first_name% %last_name% (%nickname%)',
            'date_format' => 'y',
        ]);

        $array = UserPreferencesIndexViewHelper::data($user);

        $this->assertEquals(
            9,
            count($array)
        );

        $this->assertArrayHasKey('help', $array);
        $this->assertArrayHasKey('name_order', $array);
        $this->assertArrayHasKey('date_format', $array);
        $this->assertArrayHasKey('timezone', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('number_format', $array);
        $this->assertArrayHasKey('distance_format', $array);
        $this->assertArrayHasKey('maps', $array);
        $this->assertArrayHasKey('locale', $array);

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'back' => env('APP_URL').'/settings',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_help(): void
    {
        $user = User::factory()->create([
            'help_shown' => true,
        ]);
        $array = UserPreferencesIndexViewHelper::dtoHelp($user);
        $this->assertEquals(
            [
                'help_shown' => true,
                'url' => [
                    'store' => env('APP_URL').'/settings/preferences/help',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_name_order(): void
    {
        $user = User::factory()->create([
            'name_order' => '%first_name% %last_name% (%nickname%)',
        ]);
        $array = UserPreferencesIndexViewHelper::dtoNameOrder($user);
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

        $array = UserPreferencesIndexViewHelper::dtoDateFormat($user);
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

    /** @test */
    public function it_gets_the_data_needed_for_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
        ]);
        $array = UserPreferencesIndexViewHelper::dtoTimezone($user);
        $this->assertEquals(
            [
                'timezone' => 'UTC',
                'url' => [
                    'store' => env('APP_URL').'/settings/preferences/timezone',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_number_format(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create([
            'number_format' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);

        $array = UserPreferencesIndexViewHelper::dtoNumberFormat($user);
        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('numbers', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/preferences/number',
            ],
            $array['url']
        );

        $this->assertEquals(
            User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
            $array['number_format']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => 0,
                    'format' => 'Locale default: 1,234.56',
                    'value' => User::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT,
                ],
                1 => [
                    'id' => 1,
                    'format' => '1,234.56',
                    'value' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
                ],
                2 => [
                    'id' => 2,
                    'format' => '1 234,56',
                    'value' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
                ],
                3 => [
                    'id' => 3,
                    'format' => '1.234,56',
                    'value' => User::NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL,
                ],
                4 => [
                    'id' => 4,
                    'format' => '1234.56',
                    'value' => User::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL,
                ],
            ],
            $array['numbers']->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_maps(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = User::factory()->create([
            'default_map_site' => User::MAPS_SITE_GOOGLE_MAPS,
        ]);

        $array = UserPreferencesIndexViewHelper::dtoMapsPreferences($user);
        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('types', $array);
        $this->assertArrayHasKey('default_map_site', $array);
        $this->assertArrayHasKey('default_map_site_i18n', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/preferences/maps',
            ],
            $array['url']
        );

        $this->assertEquals(
            User::MAPS_SITE_GOOGLE_MAPS,
            $array['default_map_site']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'type' => 'Google Maps',
                    'description' => trans('Google Maps offers the best accuracy and details, but it is not ideal from a privacy standpoint.'),
                    'value' => User::MAPS_SITE_GOOGLE_MAPS,
                ],
                1 => [
                    'id' => 2,
                    'type' => 'Open Street Maps',
                    'description' => trans('Open Street Maps is a great privacy alternative, but offers less details.'),
                    'value' => User::MAPS_SITE_OPEN_STREET_MAPS,
                ],
            ],
            $array['types']->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_locale(): void
    {
        $user = User::factory()->create([
            'locale' => 'fr',
        ]);
        $array = UserPreferencesIndexViewHelper::dtoLocale($user);
        $this->assertEquals('fr', $array['id']);
        $this->assertEquals('Français', $array['name']);
        $this->assertEquals('ltr', $array['dir']);
        $this->assertEquals([
            'store' => env('APP_URL').'/settings/preferences/locale',
        ], $array['url']);
    }
}
