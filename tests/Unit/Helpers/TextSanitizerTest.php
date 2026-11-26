<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\TextSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextSanitizerTest extends TestCase
{
    #[Test]
    public function it_keeps_an_ampersand_as_typed(): void
    {
        $this->assertSame('Dunder & Mifflin', TextSanitizer::plainText('Dunder & Mifflin'));
    }

    #[Test]
    public function it_keeps_angle_brackets_that_do_not_form_a_tag(): void
    {
        $this->assertSame('Michael 3 < 4 > 2 Dwight', TextSanitizer::plainText('Michael 3 < 4 > 2 Dwight'));
    }

    #[Test]
    public function it_keeps_an_entity_typed_literally_as_typed(): void
    {
        $this->assertSame('Schrute &amp; Beets', TextSanitizer::plainText('Schrute &amp; Beets'));
        $this->assertSame('&lt;script&gt;', TextSanitizer::plainText('&lt;script&gt;'));
    }

    #[Test]
    public function it_removes_tags(): void
    {
        $this->assertSame('Jim', TextSanitizer::plainText('<b>Jim</b>'));
        $this->assertSame('Pam', TextSanitizer::plainText('<script>alert(1)</script>Pam'));
    }

    #[Test]
    public function it_trims_the_text(): void
    {
        $this->assertSame('Stanley', TextSanitizer::plainText("  Stanley \n"));
    }

    #[Test]
    public function it_returns_null_for_a_null_or_empty_nullable_plain_text(): void
    {
        $this->assertNull(TextSanitizer::nullablePlainText(null));
        $this->assertNull(TextSanitizer::nullablePlainText('   '));
        $this->assertNull(TextSanitizer::nullablePlainText('<b></b>'));
        $this->assertSame('Dunder & Mifflin', TextSanitizer::nullablePlainText('Dunder & Mifflin'));
    }

    #[Test]
    public function it_returns_html_free_text_from_nullable_html(): void
    {
        $this->assertSame('Dunder & Mifflin', TextSanitizer::nullableHtml('<p>Dunder &amp; Mifflin</p>'));
        $this->assertNull(TextSanitizer::nullableHtml(null));
        $this->assertNull(TextSanitizer::nullableHtml('<p> </p>'));
    }
}
