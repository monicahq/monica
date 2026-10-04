<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\PasswordRequirements;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordRequirementsTest extends TestCase
{
    #[Test]
    public function it_asks_for_eight_characters_when_the_instance_sets_no_rule(): void
    {
        Password::defaults(fn (): ?Password => null);

        $this->assertEquals('minlength: 8;', PasswordRequirements::passwordRules());
        $this->assertEquals('Minimum 8 characters.', PasswordRequirements::description());
    }

    #[Test]
    public function it_states_every_rule_somebody_can_follow_while_typing(): void
    {
        Password::defaults(fn (): Password => Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised());

        $this->assertEquals(
            'minlength: 12; required: lower; required: upper; required: digit; required: special;',
            PasswordRequirements::passwordRules(),
        );
        $this->assertEquals(
            'Minimum 12 characters. Upper and lower case letters. At least one number. At least one symbol.',
            PasswordRequirements::description(),
        );
    }

    #[Test]
    public function it_asks_for_any_letter_when_the_case_does_not_matter(): void
    {
        Password::defaults(fn (): Password => Password::min(10)->letters());

        $this->assertEquals('minlength: 10; required: lower, upper;', PasswordRequirements::passwordRules());
        $this->assertEquals('Minimum 10 characters. At least one letter.', PasswordRequirements::description());
    }
}
