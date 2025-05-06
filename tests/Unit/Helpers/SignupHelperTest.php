<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SignupHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(SignupHelper::class)]
class SignupHelperTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    #[DataProvider('is_enabled_data_provider')]
    public function check_is_enabled(bool $isSignupDisabled, bool $hasAtLeastOneAccount, bool $expectedResult): void
    {
        config(['monica.disable_signup' => $isSignupDisabled]);

        if ($hasAtLeastOneAccount) {
            $this->createAccount();
        }

        $helper = $this->app[SignupHelper::class];

        $this->assertEquals($expectedResult, $helper->isEnabled());
    }

    public static function is_enabled_data_provider(): iterable
    {
        // $isSignupDisabled, $hasAtLeastOneAccount, $expectedResult
        return [
            [true, true, false],
            [true, false, true],
            [false, true, true],
            [false, false, true],
        ];
    }

    #[Test]
    public function check_is_disabled_by_config(): void
    {
        config(['monica.disable_signup' => true]);

        $helper = $this->app[SignupHelper::class];
        $this->assertTrue($this->invokePrivateMethod($helper, 'isDisabledByConfig'));
    }
}
