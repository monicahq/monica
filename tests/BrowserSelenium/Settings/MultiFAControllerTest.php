<?php

namespace Tests\BrowserSelenium\Settings;

use App\User;
use QrReader;
use Tests\BrowserSelenium\BaseTestCase;

class MultiFAControllerTest extends BaseTestCase
{
    protected function getUrl()
    {
        return '/settings/security';
    }

    /**
     * init.
     */
    public function openPage()
    {
        $this->cleanup();

        $this->initAndLogin();
        $this->assertEquals('/settings/security', $this->getCurrentPath());
    }

    /**
     * Cleanup.
     */
    public function cleanup()
    {
        exec('php artisan 2fa:deactivate --force --email=admin@admin.com', $output);
        $this->log(implode($output));
    }

    /**
     * Test if the user has 2fa Enable Link in Security Page.
     * @group multifa
     */
    public function testHasSettings2faEnableLink()
    {
        $this->openPage();
        $enableuri = $this->getDestUri('/settings/security/2fa-enable');

        $enablelinks = $this->findMultipleByXpath("//a[@href='$enableuri']");
        $this->assertGreaterThan(0, count($enablelinks), 'link /settings/security/2fa-enable not found');
        $this->assertCount(1, $enablelinks, 'too many /settings/security/2fa-enable links');

        $enablelink = $enablelinks[0];
        $this->assertEquals('btn btn-primary', $enablelink->getAttribute('class'));
    }

    /**
     * Test the barcode generated in 2fa Enable Page.
     * @group multifa
     */
    public function testHas2faEnableBarCode()
    {
        $this->openPage();

        $this->clickDestUri('/settings/security/2fa-enable');

        $barcodes = $this->findMultipleById('barcode');
        $this->assertGreaterThan(0, count($barcodes), 'barcode not present');
        $this->assertCount(1, $barcodes, 'too many barcodes');

        $secretkeys = $this->findMultipleById('secretkey');
        $this->assertGreaterThan(0, count($secretkeys), 'secretkey not present');
        $this->assertCount(1, $secretkeys, 'too many secretkeys');
    }

    /**
     * Test the barcode generated in 2fa Enable Page.
     * @group multifa
     * @group multifabarcode
     */
    public function testBarCodeContent()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        $barcode = $this->findById('barcode');
        $imgsrc = $barcode->getAttribute('src');

        $key = $this->unparseBarcode($imgsrc);
        $this->assertEquals(32, strlen($key));

        $secretkey = $this->findById('secretkey');
        $this->log('secret key: %s', $secretkey->getText());

        $this->assertEquals($secretkey->getText(), $key);
    }

    private function unparseBarcode($imgsrc)
    {
        $this->assertStringStartsWith('data:image/png', $imgsrc);

        $imgcode = str_replace('data:image/png;base64,', '', $imgsrc);
        $this->log('img code: %s', $imgcode);

        $qrcode = new QrReader(base64_decode($imgcode), QrReader::SOURCE_TYPE_BLOB);
        $text = $qrcode->text();
        $this->log('img content: %s', $text);
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
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        $secretkey = $this->findById('secretkey')->getText();
        $this->log('secret key: %s', $secretkey);

        $input = $this->findById('one_time_password');
        $input->sendKeys('000000');

        $this->findByTag('button')->submit();

        $this->assertTrue($this->hasDivAlert());
        $divalert = $this->getDivAlert();
        $this->assertContains('alert-danger', $divalert->getAttribute('class'));
        $this->assertContains('Two Factor Authentication', $divalert->getText());
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2fa()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        try {
            $this->enable2fa();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2faLoginWrongCode()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        try {
            $this->enable2fa();
            //$this->clickDestUri('/logout');
            $link = $this->findByXpath("//a[@href='/logout']");
            $link->click();
            $this->initAndLogin('/dashboard');
            $this->assertEquals('/dashboard', $this->getCurrentPath());

            $inputs = $this->findMultipleById('one_time_password');
            $this->assertCount(1, $inputs, 'Authentication code input not present');
            $input = $inputs[0];

            $input->sendKeys('000000');
            $this->findByTag('button')->submit();

            $this->assertTrue($this->hasDivAlert());
            $divalert = $this->getDivAlert();
            $this->assertContains('alert-danger', $divalert->getAttribute('class'));
            $this->assertContains('two factor authentication', $divalert->getText());
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Test the 2fa Enable Page.
     * @group multifa
     */
    public function testEnable2faLogin()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        try {
            $secretkey = $this->enable2fa();
            //$this->clickDestUri('/logout');
            $link = $this->findByXpath("//a[@href='/logout']");
            $link->click();
            $this->initAndLogin('/dashboard');
            $this->assertEquals('/dashboard', $this->getCurrentPath());

            $inputs = $this->findMultipleById('one_time_password');
            $this->assertCount(1, $inputs, 'Authentication code input not present');
            $input = $inputs[0];

            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $one_time_password = $google2fa->getCurrentOtp($secretkey);
            $input->sendKeys($one_time_password);
            $this->findByTag('button')->submit();

            $this->assertFalse($this->hasDivAlert());
            $this->assertEquals('/validate2fa', $this->getCurrentPath());
            $dashboardlinks = $this->findMultipleByXpath("//a[@href='/dashboard']");
            $this->assertGreaterThan(0, count($dashboardlinks));
        } finally {
            $this->cleanup();
        }
    }

    private function enable2fa()
    {
        $secretkey = $this->findById('secretkey')->getText();
        $this->log('secret key: %s', $secretkey);

        $input = $this->findById('one_time_password');

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $one_time_password = $google2fa->getCurrentOtp($secretkey);
        $input->sendKeys($one_time_password);

        $this->findByTag('button')->submit();

        $this->assertTrue($this->hasDivAlert());
        $divalert = $this->getDivAlert();
        $this->assertContains('alert-success', $divalert->getAttribute('class'));
        $this->assertContains('Two Factor Authentication', $divalert->getText());

        // TODO: test if user has 2fa enabled actually
        // TODO: test if session token auth is right

        $disableuri = $this->getDestUri('/settings/security/2fa-disable');

        $disablelinks = $this->findMultipleByXpath("//a[@href='$disableuri']");
        $this->assertGreaterThan(0, count($disablelinks), 'link /settings/security/2fa-disable not found');
        $this->assertCount(1, $disablelinks, 'too many /settings/security/2fa-disable links');

        $disablelink = $disablelinks[0];
        $this->assertEquals('btn btn-warning', $disablelink->getAttribute('class'));

        return $secretkey;
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2fa()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        try {
            $secretkey = $this->enable2fa();
            $this->clickDestUri('/settings/security/2fa-disable');

            $input = $this->findById('one_time_password');

            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            $one_time_password = $google2fa->getCurrentOtp($secretkey);
            $input->sendKeys($one_time_password);

            $this->findByTag('button')->submit();

            $this->assertTrue($this->hasDivAlert());
            $divalert = $this->getDivAlert();
            $this->assertContains('alert-success', $divalert->getAttribute('class'));
            $this->assertContains('Two Factor Authentication', $divalert->getText());

            // TODO: test if user has 2fa disabled actually
            // TODO: test if session token auth is right
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Test 2fa Enable Page and Disable Page.
     * @group multifa
     */
    public function testEnable2faDisable2faWrongCode()
    {
        $this->openPage();
        $this->clickDestUri('/settings/security/2fa-enable');

        try {
            $this->enable2fa();
            $this->clickDestUri('/settings/security/2fa-disable');

            $input = $this->findById('one_time_password');
            $input->sendKeys('000000');

            $this->findByTag('button')->submit();

            $this->assertTrue($this->hasDivAlert());
            $divalert = $this->getDivAlert();
            $this->assertContains('alert-danger', $divalert->getAttribute('class'));
            $this->assertContains('Two Factor Authentication', $divalert->getText());
        } finally {
            $this->cleanup();
        }
    }
}
