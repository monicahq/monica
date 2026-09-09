<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\OfferKindEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_one_column_per_offer(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('md:grid-cols-3', escape: false);
        $rendered->assertSee('Monthly subscription', escape: false);
        $rendered->assertSee('Yearly subscription', escape: false);
        $rendered->assertSee('Lifetime access', escape: false);
    }

    #[Test]
    public function it_names_the_price_and_what_the_offer_is(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('€9.00/month', escape: false);
        $rendered->assertSee('€199.00 once', escape: false);
        $rendered->assertSee('Renews every month until you cancel it.', escape: false);
        $rendered->assertSee('Pay once and keep full access for good.', escape: false);
    }

    #[Test]
    public function it_posts_one_form_per_offer_to_the_checkout(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        foreach (['price_monthly', 'price_yearly', 'price_lifetime'] as $priceId) {
            $rendered->assertSee(route('settings.checkout.store', ['price' => $priceId]), escape: false);
        }

        $rendered->assertSee('Subscribe monthly', escape: false);
        $rendered->assertSee('Buy lifetime access', escape: false);
    }

    #[Test]
    public function it_needs_no_script_of_its_own(): void
    {
        $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()])
            ->assertDontSee('<script', escape: false);
    }

    #[Test]
    public function it_draws_nothing_when_there_is_nothing_on_offer(): void
    {
        $this->blade('<x-offer-list :offers="$offers" />', ['offers' => []])
            ->assertDontSee('Monthly subscription', escape: false);
    }

    #[Test]
    public function it_is_a_list_of_headings_that_a_reader_can_walk(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('<ul', escape: false);
        $rendered->assertSee('<li', escape: false);
        $rendered->assertSee('<h3', escape: false);
    }

    #[Test]
    public function every_offer_is_taken_up_with_a_real_button_that_shows_its_focus(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('<button', escape: false);
        $rendered->assertSee('focus-visible:ring', escape: false);
    }

    #[Test]
    public function it_names_the_offer_in_words_rather_than_by_position_or_colour(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        foreach (['Monthly subscription', 'Yearly subscription', 'Lifetime access'] as $label) {
            $rendered->assertSee($label, escape: false);
        }
    }

    /** @return array<int, array{kind: OfferKindEnum, price: string, price_id: string, upgrade: bool}> */
    private function offers(): array
    {
        return [
            ['kind' => OfferKindEnum::Monthly, 'price' => '€9.00/month', 'price_id' => 'price_monthly', 'upgrade' => false],
            ['kind' => OfferKindEnum::Yearly, 'price' => '€90.00/year', 'price_id' => 'price_yearly', 'upgrade' => false],
            ['kind' => OfferKindEnum::Lifetime, 'price' => '€199.00 once', 'price_id' => 'price_lifetime', 'upgrade' => false],
        ];
    }
}
