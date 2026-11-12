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

    #[Test]
    public function it_keeps_a_name_written_in_japanese(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: '田中太郎',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('田中太郎', $slug);
    }

    #[Test]
    public function it_does_not_romanise_a_name_written_in_greek(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: 'Γιώργος Παπαδόπουλος',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('γιώργος-παπαδόπουλος', $slug);
    }

    #[Test]
    public function it_builds_a_readable_slug_from_a_latin_name(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: 'Dr Jim Halpert Jr',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('dr-jim-halpert-jr', $slug);
    }

    #[Test]
    public function it_falls_back_when_a_name_leaves_nothing_readable(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: '🎉 !!! ???',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('contact', $slug);
    }

    #[Test]
    public function it_drops_a_character_that_steers_the_direction_text_reads_in(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: "Jim\u{202E}Halpert\u{200F}",
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals('jimhalpert', $slug);
    }

    #[Test]
    public function it_settles_two_names_that_look_alike_on_one_spelling(): void
    {
        $composed = SlugBuilder::readable(
            derivedFrom: "Beno\u{00EE}t",
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $decomposed = SlugBuilder::readable(
            derivedFrom: "Beno\u{0069}\u{0302}t",
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals($composed, $decomposed);
        $this->assertEquals('benoît', $composed);
    }

    #[Test]
    public function it_tells_apart_two_names_that_only_look_alike(): void
    {
        $latin = SlugBuilder::readable(
            derivedFrom: 'ana',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $cyrillic = SlugBuilder::readable(
            derivedFrom: "\u{0430}na",
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertNotEquals($latin, $cyrillic);
    }

    #[Test]
    public function it_lowercases_a_turkish_name_the_same_way_whatever_the_locale(): void
    {
        $slug = fn (): string => SlugBuilder::readable(
            derivedFrom: 'ISTANBUL',
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->app->setLocale('en');
        $english = $slug();

        $this->app->setLocale('tr_TR');
        $turkish = $slug();

        $this->assertEquals($english, $turkish);
        $this->assertEquals('istanbul', $english);
    }

    #[Test]
    public function it_shortens_a_readable_slug_to_the_room_the_column_has(): void
    {
        $slug = SlugBuilder::readable(
            derivedFrom: str_repeat('a', 200),
            maxLength: 128,
            whenEmpty: 'contact',
            isTaken: fn (string $slug): bool => false,
        );

        $this->assertEquals(128, mb_strlen($slug));
    }
}
