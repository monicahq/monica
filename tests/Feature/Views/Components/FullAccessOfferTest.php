<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\FullAccessOfferViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FullAccessOfferTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_a_region_naming_the_free_plan(): void
    {
        $this->strip()->assertSee('aria-label="Free plan usage"', escape: false);
    }

    #[Test]
    public function it_names_each_progress_bar_by_its_visible_label(): void
    {
        $this->strip()
            ->assertSeeInOrder(['id="full-access-offer-meter-0"', 'People', 'role="progressbar"', 'aria-labelledby="full-access-offer-meter-0"'], escape: false)
            ->assertSeeInOrder(['id="full-access-offer-meter-1"', 'Vaults', 'role="progressbar"', 'aria-labelledby="full-access-offer-meter-1"'], escape: false);
    }

    #[Test]
    public function it_reports_each_limit_to_a_screen_reader_in_words(): void
    {
        $this->strip()
            ->assertSeeInOrder(['aria-valuemin="0"', 'aria-valuemax="30"', 'aria-valuenow="25"', 'aria-valuetext="25 of 30, close to the limit"', '25 / 30'], escape: false)
            ->assertSeeInOrder(['aria-valuemin="0"', 'aria-valuemax="1"', 'aria-valuenow="1"', 'aria-valuetext="1 of 1, the limit is reached"', '1 / 1'], escape: false);
    }

    #[Test]
    public function it_keeps_the_reported_value_within_the_limit(): void
    {
        config(['monica.free_contact_limit' => 30, 'monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->count(34)->create(['vault_id' => $vault->id]);

        $this->blade('<x-full-access-offer :viewModel="$viewModel" />', ['viewModel' => new FullAccessOfferViewModel(user: $user)])
            ->assertSeeInOrder(['aria-valuemax="30"', 'aria-valuenow="30"', 'aria-valuetext="34 of 30, the limit is reached"', '34 / 30'], escape: false);
    }

    #[Test]
    public function it_sends_the_reader_to_the_plans(): void
    {
        $this->strip()
            ->assertSee(route('settings.billing.index'), escape: false)
            ->assertSee('Upgrade your account', escape: false);
    }

    #[Test]
    public function it_hides_the_offer_for_good_through_a_named_control(): void
    {
        $this->strip()
            ->assertSee('action="'.route('fullAccessOffer.update').'"', escape: false)
            ->assertSee('name="_method" value="put"', escape: false)
            ->assertSee('aria-label="Hide this offer for good"', escape: false);
    }

    private function strip(): TestView
    {
        config(['monica.free_contact_limit' => 30, 'monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Contact::factory()->count(25)->create(['vault_id' => $vault->id]);

        return $this->blade('<x-full-access-offer :viewModel="$viewModel" />', [
            'viewModel' => new FullAccessOfferViewModel(user: $user),
        ]);
    }
}
