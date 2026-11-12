<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PossessionTypeEnumTest extends TestCase
{
    #[Test]
    public function it_never_hands_a_code_to_the_translators(): void
    {
        foreach (PossessionTypeEnum::cases() as $case) {
            $this->assertEquals([], array_intersect($case->translationKeys(), PossessionTypeEnum::codes()), $case->label());
        }
    }

    #[Test]
    public function it_names_each_translation_key_once(): void
    {
        foreach (PossessionTypeEnum::cases() as $case) {
            $keys = $case->translationKeys();

            $this->assertEquals(array_values(array_unique($keys)), $keys, $case->label());
        }
    }

    #[Test]
    public function every_option_is_either_translated_or_a_code(): void
    {
        foreach (PossessionTypeEnum::cases() as $case) {
            foreach ($case->blocks() as $block) {
                foreach ($block['fields'] as $field) {
                    foreach ($field['options'] as $option) {
                        $this->assertTrue(
                            in_array($option, $case->translationKeys(), true) || in_array($option, PossessionTypeEnum::codes(), true),
                            "{$case->label()}: {$option}",
                        );
                    }
                }
            }
        }
    }

    #[Test]
    public function only_the_first_block_stands_alone_and_only_a_select_carries_options(): void
    {
        foreach (PossessionTypeEnum::cases() as $case) {
            foreach ($case->blocks() as $index => $block) {
                if ($block['name'] === null) {
                    $this->assertEquals(0, $index, $case->label());
                }

                foreach ($block['fields'] as $field) {
                    if ($field['kind'] !== PossessionFieldKindEnum::Select) {
                        $this->assertEquals([], $field['options'], "{$case->label()}: {$field['name']}");
                    }
                }
            }
        }
    }
}
