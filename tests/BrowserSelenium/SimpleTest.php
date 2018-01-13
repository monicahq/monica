<?php

namespace Tests\BrowserSelenium;

class SimpleTest extends BaseTestCase
{
    /**
     * init should be run with "before" phpunit annotation, but it doesn't work !
     */
    public function init()
    {
        $this->wd->get(self::$baseUrl);
    }

    /**
     * A basic browser test example.
     */
    public function testBasicExample()
    {
        $this->init();
        $this->assertContains('Monica', $this->wd->getTitle());
    }
}
