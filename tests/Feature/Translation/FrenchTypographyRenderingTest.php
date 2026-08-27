<?php

declare(strict_types=1);

namespace Tests\Feature\Translation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrenchTypographyRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const string NARROW = "\u{202F}";

    private const string FULL = "\u{00A0}";

    #[Test]
    public function it_renders_the_narrow_non_breaking_space_french_writes_before_a_question_mark(): void
    {
        $response = $this->withSession(['locale' => 'fr_FR'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertSee('oublié'.self::NARROW.'?', escape: false);
    }

    #[Test]
    public function it_renders_the_full_non_breaking_space_french_writes_before_a_colon(): void
    {
        $response = $this->withSession(['locale' => 'fr_FR'])->get(route('auth.register.new'));

        $response->assertOk();
        $response->assertSee('suivante'.self::FULL.':', escape: false);
    }

    #[Test]
    public function it_never_lets_an_ordinary_space_stand_before_a_french_question_mark(): void
    {
        $response = $this->withSession(['locale' => 'fr_FR'])->get(route('auth.login.new'));

        $response->assertDontSee('oublié ?', escape: false);
        $response->assertDontSee('oublié?', escape: false);
    }

    #[Test]
    public function it_never_lets_an_ordinary_space_stand_before_a_french_colon(): void
    {
        $response = $this->withSession(['locale' => 'fr_FR'])->get(route('auth.register.new'));

        $response->assertDontSee('suivante :', escape: false);
        $response->assertDontSee('suivante:', escape: false);
    }

    #[Test]
    public function it_writes_english_with_ordinary_spaces_because_english_declares_no_typography(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('auth.login.new'));

        $response->assertOk();
        $response->assertDontSee(self::NARROW, escape: false);
        $response->assertDontSee(self::FULL, escape: false);
    }
}
