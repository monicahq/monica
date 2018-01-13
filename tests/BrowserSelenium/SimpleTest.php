<?php

namespace Tests\BrowserSelenium;

class SimpleTest extends BaseTestCase
{
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
