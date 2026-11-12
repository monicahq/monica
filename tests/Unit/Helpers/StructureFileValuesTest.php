<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileValues;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureFileValuesTest extends TestCase
{
    #[Test]
    public function it_refuses_a_block_that_is_not_a_block(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::object('words', 'template', [], []),
        );
    }

    #[Test]
    public function it_refuses_a_name_this_version_does_not_know_and_says_where(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::object(['surprise' => 1], 'template', ['name'], []),
        );
    }

    #[Test]
    public function it_does_not_repeat_a_hostile_name_back_into_the_refusal(): void
    {
        try {
            StructureFileValues::object(['<script>' => 1], 'template', ['name'], []);

            $this->fail('Reading was expected to be refused.');
        } catch (StructureFileIsNotReadable $refusal) {
            $this->assertStringNotContainsString('<script>', $refusal->getMessage());
        }
    }

    #[Test]
    public function it_refuses_a_block_that_says_nothing_about_what_it_must(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::object(['name' => 'Contact'], 'template', ['name', 'pages'], ['pages']),
        );
    }

    #[Test]
    public function it_hands_back_a_block_that_says_only_what_it_may(): void
    {
        $node = ['name' => 'Contact', 'pages' => []];

        $this->assertSame($node, StructureFileValues::object($node, 'template', ['name', 'pages'], ['name']));
    }

    #[Test]
    public function it_refuses_a_list_that_is_not_a_list_and_one_that_is_too_long(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::collection(['key' => 'value'], 'template.pages', 5),
        );

        $this->assertRefused(
            fn () => StructureFileValues::collection([1, 2, 3], 'template.pages', 2),
        );
    }

    #[Test]
    public function it_reads_words_trimmed_and_calls_an_empty_string_nothing_at_all(): void
    {
        $this->assertSame('Contact', StructureFileValues::nullableString(['name' => '  Contact  '], 'name', 'template', 255));
        $this->assertNull(StructureFileValues::nullableString(['name' => '   '], 'name', 'template', 255));
        $this->assertNull(StructureFileValues::nullableString([], 'name', 'template', 255));
    }

    #[Test]
    public function it_refuses_words_that_are_not_words_and_words_that_are_too_many(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::nullableString(['name' => 12], 'name', 'template', 255),
        );

        $this->assertRefused(
            fn () => StructureFileValues::nullableString(['name' => 'Dunder'], 'name', 'template', 4),
        );
    }

    #[Test]
    public function a_word_that_has_to_be_there_falls_back_before_it_refuses(): void
    {
        $this->assertSame('contact', StructureFileValues::string([], 'applies_to', 'template', 32, 'contact'));

        $this->assertRefused(
            fn () => StructureFileValues::string([], 'applies_to', 'template', 32),
        );
    }

    #[Test]
    public function it_reads_a_key_and_refuses_anything_that_is_not_one(): void
    {
        $this->assertSame('first_name', StructureFileValues::identifier(['key' => 'first_name'], 'key', 'template', 64));
        $this->assertSame('a-b1', StructureFileValues::identifier(['key' => 'a-b1'], 'key', 'template', 64));
        $this->assertNull(StructureFileValues::identifier([], 'key', 'template', 64));

        foreach (['First_Name', 'first name', '_leading', 'trailing_', 'two__underscores'] as $refused) {
            $this->assertRefused(
                fn () => StructureFileValues::identifier(['key' => $refused], 'key', 'template', 64),
            );
        }
    }

    #[Test]
    public function it_reads_a_whole_number_inside_its_range(): void
    {
        $this->assertSame(7, StructureFileValues::nullableInteger(['position' => 7], 'position', 'template', 0, 10));
        $this->assertNull(StructureFileValues::nullableInteger([], 'position', 'template', 0, 10));
        $this->assertSame(3, StructureFileValues::integer([], 'position', 'template', 0, 10, 3));
    }

    #[Test]
    public function it_refuses_a_number_outside_its_range_or_written_as_words(): void
    {
        foreach ([11, -1, '7', 7.5] as $refused) {
            $this->assertRefused(
                fn () => StructureFileValues::nullableInteger(['position' => $refused], 'position', 'template', 0, 10),
            );
        }
    }

    #[Test]
    public function it_reads_a_yes_or_a_no_and_refuses_anything_that_is_neither(): void
    {
        $this->assertTrue(StructureFileValues::boolean(['is_required' => true], 'is_required', 'template'));
        $this->assertFalse(StructureFileValues::boolean([], 'is_required', 'template'));
        $this->assertTrue(StructureFileValues::boolean([], 'is_required', 'template', true));

        foreach (['true', 1, 0] as $refused) {
            $this->assertRefused(
                fn () => StructureFileValues::boolean(['is_required' => $refused], 'is_required', 'template'),
            );
        }
    }

    #[Test]
    public function it_reads_a_locale_this_instance_ships_and_quietly_forgets_any_other(): void
    {
        $this->assertSame('fr_FR', StructureFileValues::locale(['locale' => 'fr_FR'], 'template'));
        $this->assertNull(StructureFileValues::locale(['locale' => 'xx_XX'], 'template'));
        $this->assertNull(StructureFileValues::locale(['locale' => 'not a locale'], 'template'));
        $this->assertNull(StructureFileValues::locale([], 'template'));
    }

    #[Test]
    public function a_word_written_in_the_file_is_read_as_it_stands(): void
    {
        $node = ['label' => 'Birthday', 'label_translation_key' => 'Date of birth'];

        $this->assertSame('Birthday', StructureFileValues::word($node, 'label', 'label_translation_key', 'template', 255));
    }

    #[Test]
    public function a_word_pointing_at_nothing_translatable_keeps_the_reference_it_named(): void
    {
        $this->assertSame(
            'Nothing translates this',
            StructureFileValues::word(['label_translation_key' => 'Nothing translates this'], 'label', 'label_translation_key', 'template', 255),
        );
    }

    #[Test]
    public function a_translation_longer_than_what_can_be_stored_is_cut_to_fit(): void
    {
        app()->setLocale('fr_FR');

        $node = ['label_translation_key' => 'Add a page'];

        $word = StructureFileValues::word($node, 'label', 'label_translation_key', 'template', 12);

        $this->assertNotSame('Add a page', $word);
        $this->assertLessThanOrEqual(12, mb_strlen($word));
    }

    #[Test]
    public function it_refuses_a_block_that_names_no_word_at_all(): void
    {
        $this->assertRefused(
            fn () => StructureFileValues::word([], 'label', 'label_translation_key', 'template', 255),
        );
    }

    private function assertRefused(callable $reading): void
    {
        try {
            $reading();
        } catch (StructureFileIsNotReadable $refusal) {
            $this->assertInstanceOf(StructureFileIsNotReadable::class, $refusal);

            return;
        }

        $this->fail('Reading was expected to be refused.');
    }
}
