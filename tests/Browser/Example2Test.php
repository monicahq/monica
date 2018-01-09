<?php

namespace Tests\Browser;

class Example2Test extends MyAbstractTestCase
{
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
        $this->assertContains('xx', $this->wd->getTitle());
    }
}
