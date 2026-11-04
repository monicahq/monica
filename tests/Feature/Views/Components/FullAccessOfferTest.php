<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FullAccessOfferTest extends TestCase
{
    #[Test]
    public function it_offers_full_access_and_sends_the_reader_to_the_plans(): void
    {
        $this->blade('<x-full-access-offer />')
            ->assertSee('Subscribe now to lift the limits of the free plan!', escape: false)
            ->assertSee(route('settings.billing.index'), escape: false)
            ->assertSee('See the plans', escape: false);
    }

    #[Test]
    public function it_is_a_region_named_by_its_sentence(): void
    {
        $this->blade('<x-full-access-offer />')
            ->assertSee('aria-labelledby="full-access-offer-text"', escape: false)
            ->assertSee('id="full-access-offer-text"', escape: false);
    }

    #[Test]
    public function it_hides_the_offer_for_good_through_a_named_control(): void
    {
        $this->blade('<x-full-access-offer />')
            ->assertSee('action="'.route('fullAccessOffer.update').'"', escape: false)
            ->assertSee('name="_method" value="put"', escape: false)
            ->assertSee('aria-label="Hide this offer for good"', escape: false);
    }
}
