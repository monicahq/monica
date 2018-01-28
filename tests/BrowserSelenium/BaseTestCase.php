<?php

namespace Tests\BrowserSelenium;

use Lmc\Steward\ConfigProvider;
use Lmc\Steward\Test\AbstractTestCase;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Abstract class for custom tests, could eg. define some properties or instantiate some common components in setUp().
 */
abstract class BaseTestCase extends AbstractTestCase
{
    /** @var int Default width of browser window (Steward's default is 1280) */
    public static $browserWidth = 1280;
    /** @var int Default height of browser window (Steward's default is 1024) */
    public static $browserHeight = 1024;
    /** @var string */
    public static $baseUrl;

    abstract protected function getUrl();

    public function setUp()
    {
        parent::setUp();

        // Set base url according to environment
        switch (ConfigProvider::getInstance()->env) {
            case 'dev':
                self::$baseUrl = config('app.url');
                break;
            case 'travis':
                self::$baseUrl = 'http://localhost:8000/';
                break;
            case 'laravel':
                self::$baseUrl = 'http://127.0.0.1:8000/';
                break;
            case 'local':
                self::$baseUrl = 'http://127.0.0.1/';
                break;
            default:
                throw new \RuntimeException(sprintf('Unknown environment "%s"', ConfigProvider::getInstance()->env));
        }

        $this->debug('Base URL set to "%s"', self::$baseUrl);

        if (ConfigProvider::getInstance()->env == 'production') {
            $this->warn('The tests are run against production, so be careful!');
        }
    }

    /**
     * Init the WebDriver.
     * (init should be run with "before" phpunit annotation, but it doesn't work !).
     */
    public function init($url = null)
    {
        $uri = self::$baseUrl;
        if (! isset($url) || $url == null) {
            $url = '';
        }
        if (ends_with($uri, '/')) {
            $uri = substr($uri, 0, strlen($uri) - 1);
        }

        if (! starts_with($url, '/')) {
            $url = '/'.$url;
        }

        $this->wd->get($uri.$url);
    }

    /**
     * Init WebDriver and pass the login form.
     */
    public function initAndLogin($url = null, $login = 'admin@admin.com', $password = 'admin')
    {
        if (! isset($url) || $url == null) {
            $url = $this->getUrl();
            if ($url == null) {
                $url = '/dashboard';
            }
        }
        $this->init($url);

        switch ($this->getCurrentPath()) {
            case '/':
            case '/login':
                $this->findById('email')->sendKeys($login);
                $this->findById('password')->sendKeys($password);
                $this->findByTag('button')->submit();

                $this->wd->wait()->until(
                    WebDriverExpectedCondition::urlContains($url)
                );
            break;
        }
    }

    /**
     * Get the current url path.
     */
    public function getCurrentPath()
    {
        return urldecode(
            parse_url($this->wd->getCurrentURL(), PHP_URL_PATH)
        );
    }

    /**
     * Get full uri for destination path.
     */
    public function getDestUri($path)
    {
        $parse_url = parse_url($this->wd->getCurrentURL());
        $scheme = isset($parse_url['scheme']) ? $parse_url['scheme'].'://' : '';
        $host = isset($parse_url['host']) ? $parse_url['host'] : '';
        $port = isset($parse_url['port']) ? ':'.$parse_url['port'] : '';

        if (starts_with($path, '/')) {
            $path = substr($path, 1);
        }

        return $scheme.$host.$port.'/'.$path;
    }

    /**
     * Get url for path, find correponding link, and click it.
     */
    public function clickDestUri($path)
    {
        $uri = $this->getDestUri($path);
        $link = $this->findByXpath("//a[@href='$uri']");
        $link->click();
        $this->wd->wait()->until(
            WebDriverExpectedCondition::urlContains($path)
        );
    }

    public function hasDivAlert()
    {
        $res = $this->findMultipleByClass('alert');

        return count($res) > 0;
    }

    public function getDivAlert()
    {
        $res = $this->findMultipleByClass('alert');
        if (count($res) > 0) {
            return $res[0];
        }
    }
}
