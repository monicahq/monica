<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeterTest extends TestCase
{
    #[Test]
    public function it_gives_its_value_and_its_name_to_assistive_technologies(): void
    {
        $view = $this->blade('<x-meter :value="45" label="Microchip" />');

        $view->assertSee('role="meter"', false);
        $view->assertSee('aria-valuenow="45"', false);
        $view->assertSee('aria-label="Microchip"', false);
    }

    #[Test]
    public function it_keeps_its_value_between_zero_and_one_hundred(): void
    {
        $view = $this->blade('<x-meter :value="140" label="Birth date" />');

        $view->assertSee('aria-valuenow="100"', false);
    }
}
