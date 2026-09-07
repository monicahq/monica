<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LanguagePickerTest extends TestCase
{
    #[Test]
    public function it_names_every_language_it_is_given_and_marks_the_one_in_use(): void
    {
        $rendered = $this->blade(
            '<x-language-picker :locales="$locales" :current="$current" />',
            ['locales' => $this->locales(), 'current' => 'fr_FR'],
        );

        $rendered->assertSee('English', escape: false);
        $rendered->assertSee('Français', escape: false);
        $rendered->assertSee('United Kingdom', escape: false);
        $rendered->assertSee('aria-current="true"', escape: false);
    }

    #[Test]
    public function it_draws_each_flag_as_a_background_rather_than_an_image(): void
    {
        $rendered = $this->blade(
            '<x-language-picker :locales="$locales" :current="$current" />',
            ['locales' => $this->locales(), 'current' => 'en'],
        );

        $rendered->assertSee('style="background: linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%);"', escape: false);
        $rendered->assertDontSee('<img', escape: false);
    }

    #[Test]
    public function it_scrolls_the_list_rather_than_running_off_the_screen(): void
    {
        $rendered = $this->blade(
            '<x-language-picker :locales="$locales" :current="$current" />',
            ['locales' => $this->manyLocales(), 'current' => 'en'],
        );

        $rendered->assertSee('max-h-72 overflow-y-auto overscroll-contain', escape: false);
    }

    #[Test]
    public function it_falls_back_to_the_first_language_when_the_one_in_use_is_not_offered(): void
    {
        $rendered = $this->blade(
            '<x-language-picker :locales="$locales" :current="$current" />',
            ['locales' => $this->locales(), 'current' => 'kl_GL'],
        );

        $rendered->assertDontSee('aria-current="true"', escape: false);
    }

    /** @return array<int, array{code: string, label: string, region: string, flag: string}> */
    private function locales(): array
    {
        return [
            ['code' => 'en', 'label' => 'English', 'region' => 'United Kingdom', 'flag' => 'linear-gradient(to bottom, #fff 0 50%, #012169 50%)'],
            ['code' => 'fr_FR', 'label' => 'Français', 'region' => 'France', 'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)'],
        ];
    }

    /** @return array<int, array{code: string, label: string, region: string, flag: string}> */
    private function manyLocales(): array
    {
        $locales = [];

        foreach (range(1, 31) as $index) {
            $locales[] = [
                'code' => $index === 1 ? 'en' : "l{$index}",
                'label' => "Language {$index}",
                'region' => "Region {$index}",
                'flag' => '#000',
            ];
        }

        return $locales;
    }
}
