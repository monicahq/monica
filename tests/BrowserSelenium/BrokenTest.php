<?php

namespace Tests\BrowserSelenium;

class BrokenTest extends BaseTestCase
{
    public function init()
    {
        $this->wd->get(self::$baseUrl);
    }
    
    public function testBrokenExample()
    {
        $this->init();
        $this->assertContains('BROKEN', $this->wd->getTitle());
    }
}
