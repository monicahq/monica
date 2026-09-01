<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RepetitionOrderEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepetitionOrderEnumTest extends TestCase
{
    #[Test]
    public function it_has_a_case_for_each_end(): void
    {
        $this->assertSame('newest_last', RepetitionOrderEnum::NewestLast->value);
        $this->assertSame('newest_first', RepetitionOrderEnum::NewestFirst->value);
        $this->assertSame(['newest_last', 'newest_first'], RepetitionOrderEnum::values());
    }

    #[Test]
    public function it_says_what_somebody_will_see_rather_than_how_anything_is_sorted(): void
    {
        $this->assertSame('New ones at the bottom', RepetitionOrderEnum::NewestLast->label());
        $this->assertSame('New ones at the top', RepetitionOrderEnum::NewestFirst->label());

        foreach (RepetitionOrderEnum::cases() as $case) {
            $this->assertStringNotContainsStringIgnoringCase('sort', $case->label());
            $this->assertStringNotContainsStringIgnoringCase('asc', $case->label());
            $this->assertNotSame('', $case->description());
        }
    }

    #[Test]
    public function only_one_of_them_adds_to_the_front(): void
    {
        $this->assertTrue(RepetitionOrderEnum::NewestFirst->addsToTheFront());
        $this->assertFalse(RepetitionOrderEnum::NewestLast->addsToTheFront());
    }

    #[Test]
    public function it_offers_its_name_and_its_line_for_translation(): void
    {
        foreach (RepetitionOrderEnum::cases() as $case) {
            $this->assertSame([$case->label(), $case->description()], $case->translationKeys());
        }
    }
}
