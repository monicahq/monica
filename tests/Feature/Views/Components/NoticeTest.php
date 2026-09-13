<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    #[Test]
    public function it_says_the_sentence_it_is_given(): void
    {
        $this->blade('<x-notice>Nothing to point a key at yet.</x-notice>')
            ->assertSee('Nothing to point a key at yet.', escape: false);
    }

    #[Test]
    public function it_may_be_given_a_title_above_its_sentence(): void
    {
        $rendered = $this->blade('<x-notice tone="warning" title="Every code is used">Ask for new ones.</x-notice>');

        $rendered->assertSee('Every code is used');
        $rendered->assertSee('Ask for new ones.', escape: false);
    }
}
