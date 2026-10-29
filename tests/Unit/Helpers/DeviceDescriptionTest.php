<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\DeviceDescription;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceDescriptionTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function userAgents(): array
    {
        return [
            'chrome on macos' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', 'Chrome on macOS'],
            'safari on iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari on iPhone'],
            'firefox on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0', 'Firefox on Windows'],
            'edge on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0', 'Edge on Windows'],
            'chrome on android' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36', 'Chrome on Android'],
            'safari on ipad' => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'Safari on iPad'],
            'a browser with no system' => ['Firefox/131.0', 'Firefox'],
            'a system with no browser' => ['Dunder Mifflin Scanner (Windows NT 10.0)', 'Windows'],
            'nothing at all' => [null, 'Unknown device'],
            'an empty string' => ['', 'Unknown device'],
            'a command line client' => ['curl/8.4.0', 'Unknown device'],
        ];
    }

    #[Test]
    #[DataProvider('userAgents')]
    public function it_names_the_browser_and_the_system_of_a_user_agent(?string $userAgent, string $expected): void
    {
        $this->assertSame($expected, DeviceDescription::fromUserAgent(userAgent: $userAgent));
    }
}
