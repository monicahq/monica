<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\NumberFormatEnum;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionValueFormatterTest extends TestCase
{
    #[Test]
    public function it_places_the_symbol_the_english_way_with_the_separators_of_the_person(): void
    {
        app()->setLocale('en');
        $user = new User(['number_format' => NumberFormatEnum::CommaAndDot]);

        $formatter = new PossessionValueFormatter(user: $user);

        $this->assertSame('€18,400', $formatter->format('18400.00', 'eur'));
    }

    #[Test]
    public function it_places_the_symbol_the_french_way_with_the_separators_of_the_person(): void
    {
        app()->setLocale('fr_FR');
        $user = new User(['number_format' => NumberFormatEnum::DotAndComma]);

        $formatter = new PossessionValueFormatter(user: $user);

        $this->assertSame("18.400\u{00A0}€", $formatter->format('18400.00', 'eur'));
    }

    #[Test]
    public function it_writes_no_separator_when_the_person_asked_for_none(): void
    {
        app()->setLocale('en');
        $user = new User(['number_format' => NumberFormatEnum::NoSeparator]);

        $formatter = new PossessionValueFormatter(user: $user);

        $this->assertSame('€18400', $formatter->format('18400.00', 'eur'));
    }

    #[Test]
    public function it_writes_a_dash_without_an_amount_or_a_currency(): void
    {
        $formatter = new PossessionValueFormatter(user: new User);

        $this->assertSame('—', $formatter->format(null, 'eur'));
        $this->assertSame('—', $formatter->format('2100.00', null));
    }

    #[Test]
    public function it_adds_values_per_currency_and_never_across_them(): void
    {
        app()->setLocale('en');
        $user = new User(['number_format' => NumberFormatEnum::CommaAndDot]);

        $total = new PossessionValueFormatter(user: $user)->total([
            new Possession(['declared_value' => '18400.00', 'declared_value_currency' => 'eur']),
            new Possession(['declared_value' => '2100.00', 'declared_value_currency' => 'eur']),
            new Possession(['declared_value' => '950.00', 'declared_value_currency' => 'usd']),
            new Possession(['declared_value' => null, 'declared_value_currency' => null]),
        ]);

        $this->assertSame('€20,500, $950', $total);
    }

    #[Test]
    public function it_writes_a_dash_when_nothing_carries_a_value(): void
    {
        $total = new PossessionValueFormatter(user: new User)->total([
            new Possession(['declared_value' => null]),
        ]);

        $this->assertSame('—', $total);
    }

    #[Test]
    public function it_keeps_the_cents_of_a_price_and_drops_them_from_a_round_one(): void
    {
        app()->setLocale('en');
        $formatter = new PossessionValueFormatter(user: new User(['number_format' => NumberFormatEnum::CommaAndDot]));

        $this->assertSame('$49.20', $formatter->formatExact('49.20', 'usd'));
        $this->assertSame('$1,450', $formatter->formatExact('1450.00', 'usd'));
        $this->assertSame('—', $formatter->formatExact(null, 'usd'));
    }
}
