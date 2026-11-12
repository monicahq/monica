<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OptionRowTest extends TestCase
{
    #[Test]
    public function it_makes_the_whole_row_the_target_of_the_option_it_carries(): void
    {
        $rendered = $this->blade('<x-option-row id="diet-vegetarian" name="diet" value="7">Vegetarian</x-option-row>');

        $rendered->assertSee('for="diet-vegetarian"', escape: false);
        $rendered->assertSee('name="diet"', escape: false);
        $rendered->assertSee('value="7"', escape: false);
        $rendered->assertSee('Vegetarian', escape: false);
    }
}
