<?php

namespace Tests\BrowserSelenium;

class SimpleTest extends BaseTestCase
{
    /**
     * A basic browser test example.
     */
    public function testBasicExample()
    {
        $this->init();

        $this->assertContains('Monica', $this->wd->getTitle());
        $this->assertEquals('/', $this->getCurrentPath());
    }

    /**
     * Test Login page.
     */
    public function testLogin()
    {
        $this->initAndLogin();
        $this->assertContains('Monica', $this->wd->getTitle());
        $this->assertEquals('/dashboard', $this->getCurrentPath());

        $link = $this->findByXpath("//a[@href='/logout']");
        $this->assertNotNull($link);
    }
}
