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

    #[Test]
    public function the_input_is_a_real_control(): void
    {
        $this->blade('<x-option-row id="diet" name="diet">Vegetarian</x-option-row>')
            ->assertSee('type="checkbox"', escape: false);
    }

    #[Test]
    public function it_carries_a_second_line_when_the_option_needs_explaining(): void
    {
        $this->blade('<x-option-row id="a" name="year" hint="The day and the month are enough.">I do not know the year</x-option-row>')
            ->assertSee('I do not know the year', escape: false)
            ->assertSee('The day and the month are enough.', escape: false);
    }
}
