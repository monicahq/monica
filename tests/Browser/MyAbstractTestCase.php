<?php

namespace Tests\Browser;

use Lmc\Steward\ConfigProvider;
use Lmc\Steward\Test\AbstractTestCase;

/**
 * Abstract class for custom tests, could eg. define some properties or instantiate some common components in setUp().
 */
abstract class MyAbstractTestCase extends AbstractTestCase
{
    /** @var int Default width of browser window (Steward's default is 1280) */
    public static $browserWidth = 1024;
    /** @var int Default height of browser window (Steward's default is 1024) */
    public static $browserHeight = 768;
    /** @var string */
    public static $baseUrl;

    public function setUp()
    {
        parent::setUp();

        // Set base url according to environment
        switch (ConfigProvider::getInstance()->env) {
            /*
            case 'production':
                self::$baseUrl = 'https://www.w3.org/';
                break;
            case 'staging':
                self::$baseUrl = 'http://some-staging-url/';
                break;
            */
            case 'local':
                self::$baseUrl = 'http://localhost'; //env('APP_URL');
                break;
            default:
                throw new \RuntimeException(sprintf('Unknown environment "%s"', ConfigProvider::getInstance()->env));
        }

        $this->debug('Base URL set to "%s"', self::$baseUrl);

        if (ConfigProvider::getInstance()->env == 'production') {
            $this->warn('The tests are run against production, so be careful!');
        }
    }
}
