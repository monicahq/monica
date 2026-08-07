<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

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

        $this->assertCount(4, $locales);
        $this->assertEquals([
            'label' => 'English',
            'region' => 'United Kingdom',
            'flag' => config('officelife.locales.en.flag'),
            'code' => 'en',
        ], $locales[0]);
    }

    #[Test]
    public function it_gives_the_urls_of_the_legal_pages(): void
    {
        $viewModel = new RegisterViewModel;

        $this->assertEquals(config('officelife.terms_url'), $viewModel->termsUrl());
        $this->assertEquals(config('officelife.privacy_url'), $viewModel->privacyUrl());
    }
}
