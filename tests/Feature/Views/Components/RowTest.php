<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RowTest extends TestCase
{
    #[Test]
    public function it_is_the_link_when_it_is_given_one(): void
    {
        $rendered = $this->blade('<x-row title="Templates" href="/settings/account/templates" />');

        $rendered->assertSee('<a', escape: false);
        $rendered->assertSee('href="/settings/account/templates"', escape: false);
    }

    #[Test]
    public function it_is_a_plain_division_when_it_goes_nowhere(): void
    {
        $rendered = $this->blade('<x-row title="Notifications" />');

        $rendered->assertSee('Notifications', escape: false);
        $rendered->assertDontSee('<a', escape: false);
    }
}
