<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    #[Test]
    public function it_shows_the_word_it_is_given(): void
    {
        $rendered = $this->blade('<x-badge tone="danger">Bounced</x-badge>');

        $rendered->assertSee('Bounced', escape: false);
    }
}
