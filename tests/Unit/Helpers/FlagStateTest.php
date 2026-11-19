<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlagStateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_off_for_an_account_and_for_a_visitor_while_nothing_is_switched(): void
    {
        $pam = User::factory()->create();

        $this->assertFalse(FlagState::isOn('marketplace', $pam->account));
        $this->assertFalse(FlagState::isOn('marketplace', null));
    }

    #[Test]
    public function it_answers_a_visitor_with_what_every_account_sees(): void
    {
        Feature::for(null)->activate('marketplace');

        $this->assertTrue(FlagState::isOn('marketplace', null));
    }

    #[Test]
    public function it_answers_an_account_with_its_own_switch_and_leaves_visitors_out_of_it(): void
    {
        $pam = User::factory()->create();
        $pam->account->features()->activate('marketplace');

        $this->assertTrue(FlagState::isOn('marketplace', $pam->account));
        $this->assertFalse(FlagState::isOn('marketplace', null));
    }

    #[Test]
    public function it_counts_the_accounts_switched_on_while_the_flag_is_off_for_every_account(): void
    {
        [$jim, $pam] = User::factory()->count(5)->create();
        $jim->account->features()->activate('marketplace');
        $pam->account->features()->activate('marketplace');

        $this->assertSame(2, FlagState::accountsOn('marketplace'));
    }

    #[Test]
    public function it_counts_every_account_once_the_flag_is_on_for_every_account_those_never_asked_included(): void
    {
        [$jim] = User::factory()->count(5)->create();
        FlagState::isOn('marketplace', $jim->account);

        Feature::for(null)->activate('marketplace');
        Feature::activateForEveryone('marketplace');

        $this->assertSame(5, FlagState::accountsOn('marketplace'));
    }

    #[Test]
    public function it_leaves_out_an_account_switched_off_after_the_flag_went_on_for_every_account(): void
    {
        [$dwight] = User::factory()->count(5)->create();
        Feature::for(null)->activate('marketplace');
        Feature::activateForEveryone('marketplace');

        $dwight->account->features()->deactivate('marketplace');

        $this->assertSame(4, FlagState::accountsOn('marketplace'));
    }
}
