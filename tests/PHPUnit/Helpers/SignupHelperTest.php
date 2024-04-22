<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Helpers;

use App\Helpers\SignupHelper;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignupHelper::class)]
class SignupHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[DataProvider('isEnabledDataProvider')]
    public function testIsEnabled(bool $isSignupDisabled, bool $hasAtLeastOneAccount, bool $expectedResult): void
    {
        $helper = Mockery::mock(SignupHelper::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $helper->shouldReceive('isDisabledByConfig')->andReturn($isSignupDisabled);
        $helper->shouldReceive('hasAtLeastOneAccount')->andReturn($hasAtLeastOneAccount);

        $this->assertEquals($expectedResult, $helper->isEnabled());
    }

    public function isEnabledDataProvider(): iterable
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
        $configRepository = Mockery::mock(ConfigRepository::class)->makePartial();
        $configRepository->shouldReceive('get')
            ->once()
            ->withArgs(function ($name) {
                return $name === 'monica.disable_signup';
            })
            ->andReturnTrue();

        $helper = Mockery::mock(SignupHelper::class, [$configRepository])->makePartial();
        $helper->isDisabledByConfig();
    }
}
