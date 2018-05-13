<?php

namespace Tests\Browser\Settings;

use App\User;
use Zxing\QrReader;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\SettingsSecurity;
use Tests\Browser\Pages\DashboardValidate2fa;
use Tests\Browser\Pages\SettingsSecurity2faEnable;
use Tests\Browser\Pages\SettingsSecurity2faDisable;

class MultiFAControllerTest extends DuskTestCase
{
    /**
     * Cleanup.
     */
    public function cleanup()
    {
        exec('php artisan 2fa:deactivate --force --email=admin@admin.com', $output);
        //$this->log(implode($output));
    }

    /**
     * Test if the user has 2fa Enable Link in Security Page.
     * @group multifa
     */
    public function testHasSettings2faEnableLink()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->assertSeeLink('Enable Two Factor Authentication');
        });
    }

    /**
     * Test the barcode generated in 2fa Enable Page.
     * @group multifa
     */
    public function testHas2faEnableBarCode()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable)
                    ->assertVisible('barcode')
                    ->assertVisible('secretkey');
        });
    }

    /**
     * Test the barcode generated in 2fa Enable Page.
     * @group multifa
     * @group multifabarcode
     */
    public function testBarCodeContent()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            // \Facebook\WebDriver\Remote\RemoteWebElement
            $barcode = $browser->element('barcode');
            $imgsrc = $barcode->getAttribute('src');

            $key = $this->unparseBarcode($imgsrc);
            $this->assertEquals(32, strlen($key));

            $this->assertEquals($browser->text('secretkey'), $key);
        });
    }

    private function unparseBarcode($imgsrc)
    {
        $this->assertStringStartsWith('data:image/png', $imgsrc);

        $imgcode = str_replace('data:image/png;base64,', '', $imgsrc);

        $qrcode = new QrReader(base64_decode($imgcode), QrReader::SOURCE_TYPE_BLOB);
        $text = $qrcode->text();
        $this->assertStringStartsWith('otpauth://totp/', $text);

        // unparse $text
        // See PragmaRX\Google2FA\Support\QRCode getQRCodeUrl
        // example :
        //otpauth://totp/monicalocal.test:admin%40admin.com?secret=H25L7JLI7I57KYE7U53BIIOUELWXMRE6&issuer=monicalocal.test

        $ret = preg_match('@^otpauth://totp/([^:]+):([^?]+)\?secret=([^&]+)&issuer=(.+)@i', $text, $matches);
        $this->assertEquals(1, $ret, 'otp content does not match format');
        $this->assertCount(5, $matches);

        return $matches[3];
    }

    /**
     * Test the 2fa Enable Page with wrong code.
     * @group multifa
     */
    public function testEnable2faWrongCode()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable)
                    ->type('one_time_password', '000000')
                    ->scrollTo('verify')
                    ->press('verify');

            $this->assertTrue($this->hasDivAlert($browser));
            $divalert = $this->getDivAlert($browser);
            $this->assertContains('alert-danger', $divalert->getAttribute('class'));
            $this->assertContains('Two Factor Authentication', $divalert->getText());
        });
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2fa()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            $this->enable2fa($browser);
        });
    }

    private function enable2fa(Browser $browser)
    {
        $browser->on(new SettingsSecurity2faEnable);

        $secretkey = $browser->text('secretkey');

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $one_time_password = $google2fa->getCurrentOtp($secretkey);
        $browser->type('otp', $one_time_password);

        $browser = $browser->scrollTo('verify')
            ->press('verify')
            ->on(new SettingsSecurity);

        $this->assertTrue($this->hasDivAlert($browser));
        $divalert = $this->getDivAlert($browser);
        $this->assertContains('alert-success', $divalert->getAttribute('class'));
        $this->assertContains('Two Factor Authentication', $divalert->getText());

        // TODO: test if user has 2fa enabled actually
        // TODO: test if session token auth is right

        $browser->assertSeeLink('Disable Two Factor Authentication');

        return $secretkey;
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2faLoginWrongCode()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            $secretkey = $this->enable2fa($browser);

            $browser =
            $browser->clickLink('Logout')
                    ->loginAs($user)
                    ->visit(new DashboardValidate2fa)
                    ->assertVisible('otp')
                    ->type('otp', '000000')
                    ->press('verify');

            $this->assertTrue($this->hasDivAlert($browser));
            $divalert = $this->getDivAlert($browser);
            $this->assertContains('alert-danger', $divalert->getAttribute('class'));
            $this->assertContains('The two factor authentication has failed.', $divalert->getText());
        });
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2faLogin()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            $secretkey = $this->enable2fa($browser);
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $one_time_password = $google2fa->getCurrentOtp($secretkey);

            $browser =
            $browser->clickLink('Logout')
                    ->loginAs($user)
                    ->visit(new DashboardValidate2fa)
                    ->assertVisible('otp')
                    ->type('otp', $one_time_password)
                    ->press('verify');

            $this->assertFalse($this->hasDivAlert($browser));
            $browser->assertPathIs('/validate2fa');
        });
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2fa()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            $secretkey = $this->enable2fa($browser);
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $one_time_password = $google2fa->getCurrentOtp($secretkey);

            $browser =
            $browser->visit(new SettingsSecurity2faDisable)
                    ->assertVisible('otp')
                    ->type('otp', $one_time_password)
                    ->scrollTo('verify')
                    ->press('verify');

            $this->assertTrue($this->hasDivAlert($browser));
            $divalert = $this->getDivAlert($browser);
            $this->assertContains('alert-success', $divalert->getAttribute('class'));
            $this->assertContains('Two Factor Authentication', $divalert->getText());
        });
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2faWrongCode()
    {
        $user = factory(User::class)->create();
        //$user->account->populateDefaultFields($user->account);

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->on(new SettingsSecurity2faEnable);

            $this->enable2fa($browser);

            $browser =
            $browser->visit(new SettingsSecurity2faDisable)
                    ->assertVisible('otp')
                    ->type('otp', '000000')
                    ->scrollTo('verify')
                    ->press('verify');

            $this->assertTrue($this->hasDivAlert($browser));
            $divalert = $this->getDivAlert($browser);
            $this->assertContains('alert-danger', $divalert->getAttribute('class'));
            $this->assertContains('Two Factor Authentication', $divalert->getText());
        });
    }
}
