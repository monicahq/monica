<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\MarketplaceListingRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingRejectedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_listing_without_saying_what_happened_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertEquals('About Dundie Awards in the marketplace', $message->subject);
    }

    #[Test]
    public function it_carries_the_reason_the_author_has_to_act_on(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertEquals('The description does not say what the template is for.', $message->panel);
        $this->assertStringContainsString('Dundie Awards', (string) $message->render());
    }

    #[Test]
    public function it_reads_the_reason_before_what_to_do_next(): void
    {
        $michael = User::factory()->create();

        $rendered = (string) $this->notification()->toMail($michael)->render();

        $this->assertLessThan(
            mb_strpos($rendered, 'You can work on it and submit a new version'),
            mb_strpos($rendered, 'The description does not say what the template is for.'),
        );
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notification()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_rejected_listing(): void
    {
        $this->assertEquals(EmailType::MarketplaceListingRejected, $this->notification()->emailType());
    }

    private function notification(): MarketplaceListingRejected
    {
        return new MarketplaceListingRejected(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            reason: 'The description does not say what the template is for.',
        );
    }
}
