<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvatarInitialsTest extends TestCase
{
    #[Test]
    public function it_takes_the_first_letter_of_the_first_and_last_word(): void
    {
        $this->blade('<x-avatar-initials name="Pam Beesly" />')->assertSee('>PB<', escape: false);
        $this->blade('<x-avatar-initials name="Michael Gary Scott" />')->assertSee('>MS<', escape: false);
        $this->blade('<x-avatar-initials name="Dwight" />')->assertSee('>D<', escape: false);
    }

    #[Test]
    public function it_falls_back_to_a_question_mark_when_there_is_no_name(): void
    {
        $this->blade('<x-avatar-initials name="   " />')->assertSee('>?<', escape: false);
    }

    #[Test]
    public function it_puts_the_same_name_on_the_same_tint_every_time(): void
    {
        $first = $this->blade('<x-avatar-initials name="Pam Beesly" />')->__toString();
        $second = $this->blade('<x-avatar-initials name="Pam Beesly" />')->__toString();

        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_is_hidden_from_screen_readers(): void
    {
        $this->blade('<x-avatar-initials name="Pam Beesly" />')->assertSee('aria-hidden="true"', escape: false);
    }
}
