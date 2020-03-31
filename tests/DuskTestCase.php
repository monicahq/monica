<?php

namespace Tests;

use Tests\Traits\SignIn;
use App\Models\User\User;
use Laravel\Dusk\Browser;
use App\Services\User\AcceptPolicy;
use Tests\Traits\CreatesApplication;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, SignIn;

    protected function setUp(): void
    {
        parent::setUp();
        Browser::$storeScreenshotsAt = base_path('results/screenshots');
        Browser::$storeConsoleLogAt = base_path('results/console');
        Browser::$storeSourceAt = base_path('results/source');
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        if (env('SAUCELABS') != '1') {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments(explode(' ', env('CHROME_DRIVER_OPTS', '')));
        $capabilities = DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        );

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

    /**
     * Return the default user to authenticate.
     *
     * @return \App\User|int|null
     */
    protected function user()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->account->update(['has_access_to_paid_version_for_free' => true]);

        app(AcceptPolicy::class)->execute([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'ip_address' => null,
        ]);

        return $user;
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
