<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\LocaleHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LocaleHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_get_locale_returns_english_by_default()
    {
        $this->assertEquals(
            'en',
            LocaleHelper::getLocale()
        );
    }

    public function test_get_locale_returns_right_locale_if_user_logged()
    {
        $user = $this->signIn();
        $user->locale = 'fr';
        $user->save();

        $this->assertEquals(
            'fr',
            LocaleHelper::getLocale()
        );
    }

    public function test_get_direction_default()
    {
        $this->assertEquals(
            'ltr',
            LocaleHelper::getDirection()
        );
    }

    public function test_get_direction_french()
    {
        $user = $this->signIn();
        $user->locale = 'fr';
        $user->save();

        $this->assertEquals(
            'ltr',
            LocaleHelper::getDirection()
        );
    }

    public function test_get_direction_hebrew()
    {
        $user = $this->signIn();
        $user->locale = 'he';
        $user->save();

        $this->assertEquals(
            'rtl',
            LocaleHelper::getDirection()
        );
    }
}
