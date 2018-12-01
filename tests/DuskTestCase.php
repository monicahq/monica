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


namespace Tests;

use Tests\Traits\SignIn;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, SignIn;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::useChromedriver(__DIR__.'/../vendor/bin/chromedriver');
        if (env('SAUCELABS') != '1') {
            static::startChromeDriver();
        }
    }

    protected function setUp()
    {
        parent::setUp();

        /*
         * Macro scrollTo to scroll down/up, until the selector is visible
         */
        Browser::macro('scrollTo', function ($selector) {
            //$element = $this->element($selector);
            //$this->driver->executeScript("arguments[0].scrollIntoView(true);",[$element]);

            $selectorby = $this->resolver->format($selector);
            $this->driver->executeScript("$(\"html, body\").animate({scrollTop: $(\"$selectorby\").offset().top}, 0);");

            return $this;
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $capabilities = DesiredCapabilities::chrome();
        if (env('SAUCELABS') == '1') {
            $capabilities->setCapability('tunnel-identifier', env('TRAVIS_JOB_NUMBER'));

            return RemoteWebDriver::create(
                'http://'.env('SAUCE_USERNAME').':'.env('SAUCE_ACCESS_KEY').'@localhost:4445/wd/hub', $capabilities
            );
        } else {
            return RemoteWebDriver::create(
                'http://localhost:9515', $capabilities
            );
        }
    }

    public function hasDivAlert(Browser $browser)
    {
        $res = $browser->elements('alert');

        return count($res) > 0;
    }

    public function hasNotification(Browser $browser)
    {
        $res = $browser->elements('.notifications');

        return count($res) > 0;
    }

    public function getDivAlert(Browser $browser)
    {
        $res = $browser->elements('alert');
        if (count($res) > 0) {
            return $res[0];
        }
    }

    public function getNotification($browser)
    {
        $res = $browser->elements('.notification');
        if (count($res) > 0) {
            return $res[0];
        }
    }
}
