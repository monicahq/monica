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
        $forms = CountedPhraseWordings::of(':count reminder|:count reminders', 'pl_PL');

        $keys = array_keys($forms);
        sort($keys);

        $this->assertSame(['few', 'many', 'one'], $keys);
    }

    #[Test]
    public function it_leaves_the_number_for_whoever_says_the_phrase_to_fill_in(): void
    {
        foreach (CountedPhraseWordings::of(':count reminder|:count reminders', 'fr_FR') as $wording) {
            $this->assertStringContainsString(':n', $wording);
        }
    }

    #[Test]
    public function it_gives_turkish_the_same_wording_at_every_number(): void
    {
        $forms = CountedPhraseWordings::of(':count reminder|:count reminders', 'tr_TR');

        $this->assertSame($forms['one'], $forms['other']);
    }
}
