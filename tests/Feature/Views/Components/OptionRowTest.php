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
        $rendered->assertSee('min-h-10.5 w-full', escape: false);
        $rendered->assertSee('Vegetarian', escape: false);
    }

    #[Test]
    public function the_input_is_real_and_only_the_mark_beside_it_is_drawn(): void
    {
        $this->blade('<x-option-row id="diet" name="diet">Vegetarian</x-option-row>')
            ->assertSee('type="checkbox"', escape: false)
            ->assertSee('peer sr-only', escape: false);
    }

    #[Test]
    public function the_shape_of_the_mark_says_how_many_may_be_picked(): void
    {
        $this->blade('<x-option-row type="radio" id="a" name="category">Family</x-option-row>')
            ->assertSee('type="radio"', escape: false)
            ->assertSee('rounded-full', escape: false);

        $this->blade('<x-option-row type="checkbox" id="b" name="interests">Climbing</x-option-row>')
            ->assertSee('rounded-sm', escape: false);
    }

    #[Test]
    public function a_picked_option_fills_both_its_mark_and_its_row(): void
    {
        $this->blade('<x-option-row id="a" name="category" :checked="true">Family</x-option-row>')
            ->assertSee('checked', escape: false)
            ->assertSee('has-[:checked]:bg-sand-deep', escape: false)
            ->assertSee('peer-checked:bg-ink', escape: false);
    }

    #[Test]
    public function it_shrinks_to_a_chip_when_the_answer_is_one_short_word(): void
    {
        $this->blade('<x-option-row chip id="a" name="interests[]" value="3">Climbing</x-option-row>')
            ->assertSee('inline-flex h-9', escape: false)
            ->assertDontSee('w-full', escape: false);
    }

    #[Test]
    public function it_carries_a_second_line_when_the_option_needs_explaining(): void
    {
        $this->blade('<x-option-row id="a" name="year" hint="The day and the month are enough.">I do not know the year</x-option-row>')
            ->assertSee('I do not know the year', escape: false)
            ->assertSee('The day and the month are enough.', escape: false)
            ->assertSee('items-start', escape: false);
    }
}
