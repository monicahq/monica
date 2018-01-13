<?php

namespace Tests\BrowserSelenium;

/**
 * @group basic
 */
class SimpleTest extends BaseTestCase
{
    /**
     * @before
     */
    public function init()
    {
        $this->wd->get(self::$baseUrl);
    }
    
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->assertContains('Monica', $this->wd->getTitle());
    }
}
