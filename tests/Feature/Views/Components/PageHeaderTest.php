<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageHeaderTest extends TestCase
{
    #[Test]
    public function it_names_the_screen(): void
    {
        $this->blade('<x-page-header title="Security and access" />')
            ->assertSee('Security and access', escape: false);
    }

    #[Test]
    public function it_shows_the_line_under_the_title(): void
    {
        $this->blade('<x-page-header title="Logs" description="What happened, and when." />')
            ->assertSee('What happened, and when.', escape: false);
    }

    #[Test]
    public function the_title_may_be_given_as_the_slot_instead(): void
    {
        $this->blade('<x-page-header>Kinds of record</x-page-header>')
            ->assertSee('Kinds of record', escape: false);
    }
}
