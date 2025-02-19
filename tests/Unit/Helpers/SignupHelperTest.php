<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SignupHelper;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(SignupHelper::class)]
class SignupHelperTest extends TestCase
{
    #[DataProvider('isEnabledDataProvider')]
    public function testIsEnabled(bool $isSignupDisabled, bool $hasAtLeastOneAccount, bool $expectedResult): void
    {
        $helper = Mockery::mock(SignupHelper::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $helper->shouldReceive('isDisabledByConfig')->andReturn($isSignupDisabled);
        $helper->shouldReceive('hasAtLeastOneAccount')->andReturn($hasAtLeastOneAccount);

        $this->assertEquals($expectedResult, $helper->isEnabled());
    }

    public static function isEnabledDataProvider(): iterable
    {
        // $isSignupDisabled, $hasAtLeastOneAccount, $expectedResult
        return [
            [true, true, false],
            [true, false, true],
            [false, true, true],
            [false, false, true],
        ];
    }

    public function testIsDisabledByConfig(): void
    {
        config(['monica.disable_signup' => true]);

        $helper = Mockery::mock(SignupHelper::class)->makePartial();
        $this->assertTrue($helper->isDisabledByConfig());
    }
}
