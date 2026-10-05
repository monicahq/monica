<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Actions\ActivateFlagForEveryAccount;
use App\ViewModels\Admin\FlagsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlagsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_nothing_when_no_flag_is_declared(): void
    {
        Feature::forgetDrivers();

        $this->assertSame([], new FlagsViewModel()->flags());
    }

    #[Test]
    public function it_lists_each_declared_flag_with_its_state_for_every_account_and_where_to_switch_it(): void
    {
        Queue::fake();
        Feature::forgetDrivers();
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        new ActivateFlagForEveryAccount(flag: 'example-capability')->execute();
        Feature::flushCache();

        $this->assertSame([[
            'name' => 'example-capability',
            'isOnForEveryAccount' => true,
            'activateUrl' => route('admin.flags.activation.create', ['flag' => 'example-capability']),
            'deactivateUrl' => route('admin.flags.activation.destroy', ['flag' => 'example-capability']),
        ]], new FlagsViewModel()->flags());
    }
}
