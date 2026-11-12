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
    public function it_sends_the_reader_to_the_offers(): void
    {
        $this->blade('<x-paywall-prompt :message="$message" />', ['message' => 'At the ceiling.'])
            ->assertSee(route('settings.billing.index'), escape: false);
    }
}
