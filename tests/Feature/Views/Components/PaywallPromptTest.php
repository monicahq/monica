<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaywallPromptTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_ceiling_that_was_hit(): void
    {
        $this->blade('<x-paywall-prompt :message="$message" />', ['message' => 'The free offer holds 1 vault, and this account already has it.'])
            ->assertSee('The free offer holds 1 vault, and this account already has it.', escape: false)
            ->assertSee('You are at the ceiling of the free offer', escape: false);
    }

    #[Test]
    public function it_sends_the_reader_to_the_offers(): void
    {
        $this->blade('<x-paywall-prompt :message="$message" />', ['message' => 'At the ceiling.'])
            ->assertSee(route('settings.billing.index'), escape: false)
            ->assertSee('See the offers', escape: false);
    }

    #[Test]
    public function it_warns_rather_than_alarms_and_says_so_in_words(): void
    {
        $rendered = $this->blade('<x-paywall-prompt :message="$message" />', ['message' => 'At the ceiling.']);

        $rendered->assertSee('bg-warning-tint', escape: false);
        $rendered->assertSee('You are at the ceiling', escape: false);
    }

    #[Test]
    public function it_takes_the_classes_it_is_given(): void
    {
        $this->blade('<x-paywall-prompt :message="$message" class="max-w-140" />', ['message' => 'At the ceiling.'])
            ->assertSee('max-w-140', escape: false);
    }
}
