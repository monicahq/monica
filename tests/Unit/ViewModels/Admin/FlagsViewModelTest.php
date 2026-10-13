<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Actions\ActivateFlagForEveryAccount;
use App\Models\User;
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

        $this->assertSame([], new FlagsViewModel(user: User::factory()->create())->flags());
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
        ]], new FlagsViewModel(user: User::factory()->create())->flags());
    }

    #[Test]
    public function it_offers_the_password_to_an_administrator_who_has_one(): void
    {
        $michael = User::factory()->create();

        $credentials = new FlagsViewModel(user: $michael)->credentials();

        $this->assertTrue($credentials->offersPassword());
        $this->assertFalse($credentials->offersNothing());
    }

    #[Test]
    public function it_offers_nothing_to_an_administrator_with_neither_credential(): void
    {
        $jan = User::factory()->singleSignOn()->create();

        $credentials = new FlagsViewModel(user: $jan)->credentials();

        $this->assertFalse($credentials->offersPassword());
        $this->assertFalse($credentials->offersPasskey());
        $this->assertTrue($credentials->offersNothing());
    }
}
