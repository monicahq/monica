<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Contracts\Translatable;
use PHPUnit\Framework\Attributes\Test;
use ReflectionEnum;
use Tests\TestCase;

class TranslatableEnumsTest extends TestCase
{
    #[Test]
    public function every_enum_that_carries_words_for_a_screen_says_so(): void
    {
        foreach ($this->enums() as $enum) {
            $reflection = new ReflectionEnum($enum);

            if (! $reflection->hasMethod('label') && ! $reflection->hasMethod('description')) {
                continue;
            }

            $this->assertTrue(
                is_a($enum, Translatable::class, true),
                "{$enum} carries words for a screen but does not implement Translatable, so monica:localize will never find them.",
            );
        }
    }

    #[Test]
    public function every_case_gives_at_least_one_string_to_translate(): void
    {
        foreach ($this->enums() as $enum) {
            if (! is_a($enum, Translatable::class, true)) {
                continue;
            }

            foreach ($enum::cases() as $case) {
                $this->assertNotEmpty(
                    $case->translationKeys(),
                    "{$enum}::{$case->name} says it carries words for a screen but gives none.",
                );
            }
        }
    }

    /**
     * @return list<class-string<\UnitEnum>>
     */
    private function enums(): array
    {
        $enums = [];

        foreach (glob(app_path('Enums/*.php')) ?: [] as $file) {
            $enum = 'App\\Enums\\'.basename($file, '.php');

            if (enum_exists($enum)) {
                $enums[] = $enum;
            }
        }

        $this->assertNotEmpty($enums);

        return $enums;
    }
}
