<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\CountedPhraseWordings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountedPhraseWordingsTest extends TestCase
{
    #[Test]
    public function it_gives_english_the_two_wordings_english_needs(): void
    {
        $forms = CountedPhraseWordings::of(':count week|:count weeks', 'en');

        $this->assertSame(':n week', $forms['one']);
        $this->assertSame(':n weeks', $forms['other']);
    }

    #[Test]
    public function it_gives_polish_all_three_of_its_wordings_under_the_names_the_browser_uses(): void
    {
        $forms = CountedPhraseWordings::of(':count week|:count weeks', 'pl_PL');

        $this->assertSame(':n tydzień', $forms['one']);
        $this->assertSame(':n tygodnie', $forms['few']);
        $this->assertSame(':n tygodni', $forms['many']);
    }

    #[Test]
    public function polish_reads_a_third_wording_that_two_forms_could_never_reach(): void
    {
        $forms = CountedPhraseWordings::of(':count week|:count weeks', 'pl_PL');

        $this->assertNotSame($forms['one'], $forms['few']);
        $this->assertNotSame($forms['few'], $forms['many']);
        $this->assertCount(3, array_unique($forms));
    }

    #[Test]
    public function it_leaves_the_number_for_whoever_says_the_phrase_to_fill_in(): void
    {
        foreach (CountedPhraseWordings::of(':count day|:count days', 'fr_FR') as $wording) {
            $this->assertStringContainsString(':n', $wording);
        }
    }

    #[Test]
    public function a_wording_pinned_to_one_number_carries_no_number_because_it_is_already_in_the_words(): void
    {
        $forms = CountedPhraseWordings::of('{1}every year|every :count years', 'en');

        $this->assertSame('every year', $forms['one']);
        $this->assertSame('every :n years', $forms['other']);
    }

    #[Test]
    public function it_gives_turkish_the_same_wording_at_every_number(): void
    {
        $forms = CountedPhraseWordings::of(':count month|:count months', 'tr_TR');

        $this->assertSame($forms['one'], $forms['other']);
    }

    #[Test]
    public function it_reads_the_locale_the_reader_is_on_when_none_is_named(): void
    {
        app()->setLocale('fr_FR');

        $this->assertSame(':n jour', CountedPhraseWordings::of(':count day|:count days')['one']);
    }
}
