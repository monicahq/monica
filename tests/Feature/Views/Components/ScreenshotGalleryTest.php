<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotGalleryTest extends TestCase
{
    #[Test]
    public function it_offers_one_screenshot_full_screen_without_a_way_to_another(): void
    {
        $rendered = $this->gallery(count: 1);

        $rendered->assertSee('View full screen');
        $rendered->assertSee('aria-labelledby="screenshots-01M7NBFWK5XNH908RFDGEAJXKN-label screenshots-01M7NBFWK5XNH908RFDGEAJXKN-caption-0"', escape: false);
        $rendered->assertSee('Screenshot');
        $rendered->assertDontSee('Previous screenshot');
        $rendered->assertDontSee('Next screenshot');
    }

    #[Test]
    public function it_lets_the_reader_move_between_several_screenshots_and_says_where_they_are(): void
    {
        $rendered = $this->gallery(count: 3);

        $rendered->assertSee('Previous screenshot');
        $rendered->assertSee('Next screenshot');
        $rendered->assertSee('1 of 3');
        $rendered->assertSee('aria-labelledby="screenshots-01M7NBFWK5XNH908RFDGEAJXKN-label screenshots-01M7NBFWK5XNH908RFDGEAJXKN-caption-2"', escape: false);
    }

    #[Test]
    public function it_hears_the_description_once_rather_than_from_the_caption_too(): void
    {
        $rendered = $this->gallery(count: 1);

        $rendered->assertSee('id="screenshots-01M7NBFWK5XNH908RFDGEAJXKN-caption-0" aria-hidden="true"', escape: false);
        $rendered->assertSee('alt="Dwight at the reception desk"', escape: false);
    }

    #[Test]
    public function it_opens_a_modal_dialog_named_after_the_change(): void
    {
        $rendered = $this->gallery(count: 2);

        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-modal="true"', escape: false);
        $rendered->assertSee('aria-labelledby="screenshots-01M7NBFWK5XNH908RFDGEAJXKN-title screenshots-01M7NBFWK5XNH908RFDGEAJXKN-position"', escape: false);
        $rendered->assertSee('Book the conference room from a contact');
        $rendered->assertSee('x-trap.noscroll.inert="open"', escape: false);
        $rendered->assertSee('@keydown.escape.window="open = false"', escape: false);
    }

    private function gallery(int $count): TestView
    {
        $screenshots = array_map(fn (int $position): array => [
            'url' => "https://monica.test/images/changelog/01M7NBFWK5XNH908RFDGEAJXKN-{$position}.webp",
            'description' => $position === 1 ? 'Dwight at the reception desk' : "Screenshot number {$position}",
            'width' => 1600,
            'height' => 1000,
        ], range(1, $count));

        return $this->blade(
            '<x-screenshot-gallery :screenshots="$screenshots" id="01M7NBFWK5XNH908RFDGEAJXKN" title="Book the conference room from a contact" />',
            ['screenshots' => $screenshots],
        );
    }
}
