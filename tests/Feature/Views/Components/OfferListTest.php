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
    public function it_sets_the_subscription_apart_from_the_one_time_fee(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('aria-labelledby="plans-subscription"', escape: false);
        $rendered->assertSee('aria-labelledby="plans-lifetime"', escape: false);
    }

    #[Test]
    public function it_names_the_amount_and_what_each_plan_is(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        $rendered->assertSee('€9.00', escape: false);
        $rendered->assertSee('€199.00', escape: false);
    }

    #[Test]
    public function it_posts_one_form_per_plan_to_the_checkout(): void
    {
        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $this->offers()]);

        foreach (['price_monthly', 'price_yearly', 'price_lifetime'] as $priceId) {
            $rendered->assertSee('action="'.route('settings.checkout.store', ['price' => $priceId]).'" data-turbo="false"', escape: false);
        }

    }

    #[Test]
    public function it_asks_before_switching_a_subscriber_to_a_longer_plan(): void
    {
        $offers = [
            ['kind' => OfferKindEnum::Yearly, 'price' => '€90.00/year', 'amount' => '€90.00', 'price_id' => 'price_yearly', 'upgrade' => true],
        ];

        $rendered = $this->blade('<x-offer-list :offers="$offers" />', ['offers' => $offers]);

        $rendered->assertSee(route('settings.subscription.update', ['price' => 'price_yearly']), escape: false);
        $rendered->assertDontSee(route('settings.checkout.store', ['price' => 'price_yearly']), escape: false);
    }

    /** @return array<int, array{kind: OfferKindEnum, price: string, amount: string, price_id: string, upgrade: bool}> */
    private function offers(): array
    {
        return [
            ['kind' => OfferKindEnum::Monthly, 'price' => '€9.00/month', 'amount' => '€9.00', 'price_id' => 'price_monthly', 'upgrade' => false],
            ['kind' => OfferKindEnum::Yearly, 'price' => '€90.00/year', 'amount' => '€90.00', 'price_id' => 'price_yearly', 'upgrade' => false],
            ['kind' => OfferKindEnum::Lifetime, 'price' => '€199.00 once', 'amount' => '€199.00', 'price_id' => 'price_lifetime', 'upgrade' => false],
        ];
    }
}
