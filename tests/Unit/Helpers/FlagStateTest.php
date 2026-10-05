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
}
