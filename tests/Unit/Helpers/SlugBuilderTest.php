<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SlugBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlugBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_a_slug_from_a_name(): void
    {
        $slug = SlugBuilder::build(
            wanted: null,
            derivedFrom: 'Wine bottle',
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('wine-bottle', $slug);
    }

    #[Test]
    public function it_cleans_a_slug_it_is_given(): void
    {
        $slug = SlugBuilder::build(
            wanted: 'La Cavé',
            derivedFrom: 'Wine bottle',
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('la-cave', $slug);
    }

    #[Test]
    public function it_counts_up_until_nothing_answers_to_it(): void
    {
        $taken = ['animal', 'animal-2'];

        $slug = SlugBuilder::build(
            wanted: null,
            derivedFrom: 'Animal',
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => in_array($slug, $taken, true),
        );

        $this->assertEquals('animal-3', $slug);
    }

    #[Test]
    public function it_falls_back_to_a_word_when_the_name_leaves_nothing(): void
    {
        $slug = SlugBuilder::build(
            wanted: null,
            derivedFrom: '動物',
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('type', $slug);
    }

    #[Test]
    public function it_keeps_the_slug_within_the_room_the_column_has(): void
    {
        $name = str_repeat('bottle ', 20);

        $first = SlugBuilder::build(
            wanted: null,
            derivedFrom: $name,
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => false,
        );

        $second = SlugBuilder::build(
            wanted: null,
            derivedFrom: $name,
            maxLength: 64,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => $slug === $first,
        );

        $this->assertLessThanOrEqual(64, mb_strlen($first));
        $this->assertLessThanOrEqual(64, mb_strlen($second));
        $this->assertNotEquals($first, $second);
    }

    #[Test]
    public function it_joins_the_words_with_whatever_it_is_told_to(): void
    {
        $slug = SlugBuilder::build(
            wanted: null,
            derivedFrom: 'Shoe size',
            maxLength: 64,
            whenEmpty: 'field',
            isTaken: fn (string $slug): bool => false,
            separator: '_',
        );

        $this->assertEquals('shoe_size', $slug);
    }

    #[Test]
    public function it_counts_up_with_the_separator_it_was_given(): void
    {
        $slug = SlugBuilder::build(
            wanted: null,
            derivedFrom: 'Street',
            maxLength: 64,
            whenEmpty: 'field',
            isTaken: fn (string $slug): bool => $slug === 'street',
            separator: '_',
        );

        $this->assertEquals('street_2', $slug);
    }
}
