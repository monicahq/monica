<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Helpers\LocaleCollation;
use App\ViewModels\Auth\RegisterViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterViewModelTest extends TestCase
{
    #[Test]
    public function it_gives_a_quote(): void
    {
        $quote = new RegisterViewModel()->quote();

        $this->assertArrayHasKey('text', $quote);
        $this->assertArrayHasKey('author', $quote);
        $this->assertArrayHasKey('source', $quote);
    }

    #[Test]
    public function it_gives_the_languages_the_interface_speaks(): void
    {
        $locales = new RegisterViewModel()->locales();

        $this->assertCount(count(config('monica.locales')), $locales);
        $this->assertEquals([
            'code' => 'en',
            'label' => 'English',
            'region' => 'United Kingdom',
            'flag' => config('monica.locales.en.flag'),
        ], collect($locales)->firstWhere('code', 'en'));
    }

    #[Test]
    public function it_sorts_the_languages_by_the_name_each_one_calls_itself(): void
    {
        $labels = collect(new RegisterViewModel()->locales())->pluck('label');

        $this->assertEquals(
            LocaleCollation::sortByCollator($labels, fn (string $label): string => $label)->all(),
            $labels->all(),
        );
    }

    #[Test]
    public function it_gives_the_urls_of_the_legal_pages(): void
    {
        $viewModel = new RegisterViewModel;

        $this->assertEquals(config('monica.terms_url'), $viewModel->termsUrl());
        $this->assertEquals(config('monica.privacy_url'), $viewModel->privacyUrl());
    }
}
