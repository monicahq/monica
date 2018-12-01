<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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

    public function test_format_telephone_by_iso()
    {
        $tel = LocaleHelper::formatTelephoneNumberByISO('202-555-0191', 'gb');

        $this->assertEquals(
            '+44 20 2555 0191',
            $tel
        );
    }
}
