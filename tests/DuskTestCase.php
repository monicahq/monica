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
     *
     * @return void
     */
    public static function prepare()
    {
        if (! static::runningInSail()) {
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
        $options = (new ChromeOptions)->addArguments(collect([
            '--window-size=1920,1080',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     *
     * @return bool
     */
    protected function hasHeadlessDisabled()
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Return the default user to authenticate.
     *
     * @return \App\Models\User\User
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
