<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryPickerTest extends TestCase
{
    #[Test]
    public function it_is_a_searchable_list_of_the_favourites_then_every_country(): void
    {
        $rendered = $this->blade('<x-country-picker id="address-country" name="fields[1][country]" label="Country" />');

        $rendered->assertSee('x-data="countryPicker(', escape: false);
        $rendered->assertSee('Favourites');
        $rendered->assertSee('All countries');
        $rendered->assertSee('aria-live="polite"', escape: false);
    }

    #[Test]
    public function it_still_submits_a_country_without_javascript(): void
    {
        $rendered = $this->blade(
            '<x-country-picker id="address-country" name="fields[1][country]" label="Country" :extras="$extras" :favourites="$favourites" value="keep" />',
            [
                'extras' => [['value' => '', 'label' => 'Not recorded'], ['value' => 'keep', 'label' => 'Stamford']],
                'favourites' => [['value' => '12', 'label' => 'United States']],
            ],
        );

        $rendered->assertSee('name="fields[1][country]"', escape: false);
        $rendered->assertSee('<option value="keep" selected>Stamford</option>', escape: false);
        $rendered->assertSee('<option value="12" >United States</option>', escape: false);
        $rendered->assertSee('<option value="JP" >Japan</option>', escape: false);
    }

    #[Test]
    public function it_takes_the_focus_when_asked(): void
    {
        $focused = $this->blade('<x-country-picker id="value-country" name="country_code" label="Country" autofocus />');
        $unfocused = $this->blade('<x-country-picker id="value-country" name="country_code" label="Country" />');

        $focused->assertSee('x-autofocus', escape: false);
        $unfocused->assertDontSee('x-autofocus', escape: false);
    }

    #[Test]
    public function it_sends_a_typed_name_only_when_asked_to_accept_one(): void
    {
        $accepting = $this->blade('<x-country-picker id="value-country" name="country_code" typed-name="label" label="Country" />');
        $refusing = $this->blade('<x-country-picker id="address-country" name="fields[1][country]" label="Country" />');

        $accepting->assertSee('name="label" :value="submittedName"', escape: false);
        $accepting->assertSee('acceptsATypedName: true', escape: false);
        $refusing->assertDontSee(':value="submittedName"', escape: false);
        $refusing->assertSee('acceptsATypedName: false', escape: false);
    }

    #[Test]
    public function it_sends_the_name_and_the_country_of_a_favourite_written_by_hand(): void
    {
        $rendered = $this->blade(
            '<x-country-picker id="value-country" name="country_code" typed-name="label" label="Country" :extras="$extras" value="written" />',
            ['extras' => [['value' => 'written', 'label' => 'Deutschland', 'submits' => 'DE', 'typed' => 'Deutschland']]],
        );

        $rendered->assertSee('<option value="DE" selected>Deutschland</option>', escape: false);
        $rendered->assertSee('name="label" value="Deutschland"', escape: false);
    }
}
