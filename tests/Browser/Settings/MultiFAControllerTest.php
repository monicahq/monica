<?php

namespace Tests\Browser\Settings;

use Zxing\QrReader;
use Tests\DuskTestCase;
use App\Models\User\User;
use Laravel\Dusk\Browser;
use Illuminate\Console\Application;
use Tests\Browser\Pages\SettingsSecurity;
use Tests\Browser\Pages\DashboardValidate2fa;

class MultiFAControllerTest extends DuskTestCase
{
    /**
     * Cleanup.
     */
    public function cleanup()
    {
        exec(Application::formatCommandString('2fa:deactivate --force --email=admin@admin.com'), $output);
        //$this->log(implode($output));
    }

    /**
     * Test if the user has 2fa Enable Link in Security Page.
     * @group multifa
     */
    public function testHasSettings2faEnableLink()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->assertSeeLink('Enable Two Factor Authentication');
        });
    }

    /**
     * Test if the user has WebAuthn Enable Link in Security Page.
     * @group multifa
     */
    public function testHasSettingsWebAuthnEnableLink()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->assertSeeLink('Add a new security key');
        });
    }

    /**
     * Test U2F modal.
     * @group multifa
     */
    public function testU2fModal()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Add a new U2F security key')
                    ->waitFor('registerModal')
                    ->assertSee('Insert your security key.');
        });
    }

    /**
     * Test the barcode generated in 2fa Enable Page.
     * @group multifa
     */
    public function testHas2faEnableBarCode()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal')
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
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

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
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal')
                    ->type('otpenable', '000000')
                    ->scrollTo('enableVerify')
                    ->press('enableVerify')
                    ->waitUntilMissing('enableModal');

            $this->assertTrue($this->hasNotification($browser));
            $notification = $this->getNotification($browser);
            $this->assertStringContainsString('error', $notification->getAttribute('class'));
            $this->assertStringContainsString('Two Factor Authentication', $notification->getText());
        });
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2fa()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

            $this->enable2fa($browser);
        });
    }

    private function enable2fa(Browser $browser)
    {
        $secretkey = $browser->waitFor('enableModal')
                             ->text('secretkey');

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $one_time_password = $google2fa->getCurrentOtp($secretkey);
        $browser->type('otpenable', $one_time_password);

        $browser = $browser->scrollTo('enableVerify')
            ->press('enableVerify')
            ->waitUntilMissing('enableModal');

        $this->assertTrue($this->hasNotification($browser));
        $notification = $this->getNotification($browser);
        $this->assertStringContainsString('success', $notification->getAttribute('class'));
        $this->assertStringContainsString('Two Factor Authentication', $notification->getText());

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
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

            $secretkey = $this->enable2fa($browser);

            $browser =
            $browser->clickLink('Logout')
                    ->loginAs($user)
                    ->visit(new DashboardValidate2fa)
                    ->assertVisible('otp')
                    ->type('otp', '000000')
                    ->press('verify');

            $this->assertTrue($this->hasDivAlert($browser));
            $notification = $this->getDivAlert($browser);
            $this->assertStringContainsString('alert-danger', $notification->getAttribute('class'));
            $this->assertStringContainsString('The two factor authentication has failed.', $notification->getText());
        });
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2faLogin()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

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
            $browser->assertPathIs('/dashboard');
        });
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2fa()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

            $secretkey = $this->enable2fa($browser);
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $one_time_password = $google2fa->getCurrentOtp($secretkey);

            $browser =
            $browser->clickLink('Disable Two Factor Authentication')
                    ->waitFor('disableModal')
                    ->assertVisible('otpdisable')
                    ->type('otpdisable', $one_time_password)
                    ->scrollTo('disableVerify')
                    ->press('disableVerify')
                    ->waitUntilMissing('enableModal');

            $this->assertTrue($this->hasNotification($browser));
            $notification = $this->getNotification($browser);
            $this->assertStringContainsString('success', $notification->getAttribute('class'));
            $this->assertStringContainsString('Two Factor Authentication', $notification->getText());
        });
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2faWrongCode()
    {
        $this->markTestIncomplete('Ignore 2fa tests for now.');

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser =
            $browser->loginAs($user)
                    ->visit(new SettingsSecurity)
                    ->scrollTo('two_factor_link')
                    ->clickLink('Enable Two Factor Authentication')
                    ->waitFor('enableModal');

            $this->enable2fa($browser);

            $browser =
            $browser->clickLink('Disable Two Factor Authentication')
                    ->waitFor('disableModal')
                    ->assertVisible('otpdisable')
                    ->type('otpdisable', '000000')
                    ->scrollTo('disableVerify')
                    ->press('disableVerify')
                    ->waitUntilMissing('disableModal');

            $this->assertTrue($this->hasNotification($browser));

            $res = $browser->elements('.notification');
            $notification = $res[1];

            $this->assertStringContainsString('error', $notification->getAttribute('class'));
            $this->assertStringContainsString('Two Factor Authentication', $notification->getText());
        });
    }
}
