<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\CurrencyEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyPickerTest extends TestCase
{
    #[Test]
    public function it_offers_every_currency_as_an_option_named_in_full(): void
    {
        $rendered = $this->blade('<x-currency-picker id="currency" label="Currency" />');

        $rendered->assertSee('role="listbox"', escape: false);
        $rendered->assertSee('aria-haspopup="listbox"', escape: false);
        $rendered->assertSee('aria-expanded="false"', escape: false);
        $rendered->assertSee('aria-labelledby="currency-label currency"', escape: false);
        $this->assertSame(count(CurrencyEnum::cases()), mb_substr_count((string) $rendered, 'role="option"'));
    }

    #[Test]
    public function it_marks_the_chosen_currency_and_submits_it(): void
    {
        $rendered = $this->blade(
            '<x-currency-picker id="tx-currency" name="currency" label="Currency" :value="$value" />',
            ['value' => CurrencyEnum::SwissFranc],
        );

        $rendered->assertSee('name="currency"', escape: false);
        $rendered->assertSee('value="chf"', escape: false);
        $this->assertSame(1, mb_substr_count((string) $rendered, 'aria-selected="true"'));
    }

    #[Test]
    public function it_announces_an_error_on_the_control(): void
    {
        $this->blade('<x-currency-picker id="currency" label="Currency" error="Choose the currency of these amounts." />')
            ->assertSee('aria-invalid="true"', escape: false)
            ->assertSee('aria-describedby="currency-error"', escape: false);
    }
}
