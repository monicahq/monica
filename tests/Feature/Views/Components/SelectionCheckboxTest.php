<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SelectionCheckboxTest extends TestCase
{
    #[Test]
    public function it_is_a_real_checkbox_named_by_its_label(): void
    {
        $this->blade('<x-selection-checkbox label="Select Jim Halpert" data-contact-uuid="jim" />')
            ->assertSee('type="checkbox"', escape: false)
            ->assertSee('aria-label="Select Jim Halpert"', escape: false)
            ->assertSee('data-contact-uuid="jim"', escape: false)
            ->assertDontSee('role="checkbox"', escape: false);
    }
}
