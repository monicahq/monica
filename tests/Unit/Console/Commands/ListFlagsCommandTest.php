<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Actions\ActivateFlagForEveryAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListFlagsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_when_no_flag_is_declared(): void
    {
        Feature::forgetDrivers();

        $this->artisan('monica:list-flags')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_lists_each_declared_flag_with_its_state_for_every_account(): void
    {
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        $this->artisan('monica:list-flags')
            ->expectsOutputToContain('example-capability: off for every account')
            ->assertExitCode(0);

        new ActivateFlagForEveryAccount(flag: 'example-capability')->execute();
        Feature::flushCache();

        $this->artisan('monica:list-flags')
            ->expectsOutputToContain('example-capability: on for every account')
            ->assertExitCode(0);
    }
}
