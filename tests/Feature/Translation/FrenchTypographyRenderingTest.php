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
}
