<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MethodBadgeTest extends TestCase
{
    #[Test]
    public function it_writes_the_verb_out_rather_than_only_colouring_it(): void
    {
        $this->blade('<x-api-docs.method-badge method="DELETE" />')->assertSee('DELETE');
        $this->blade('<x-api-docs.method-badge method="GET" />')->assertSee('GET');
    }

    #[Test]
    public function each_verb_takes_the_colour_the_design_gives_it(): void
    {
        $this->blade('<x-api-docs.method-badge method="GET" />')->assertSee('text-brand', escape: false);
        $this->blade('<x-api-docs.method-badge method="POST" />')->assertSee('text-success', escape: false);
        $this->blade('<x-api-docs.method-badge method="PUT" />')->assertSee('text-warning', escape: false);
        $this->blade('<x-api-docs.method-badge method="PATCH" />')->assertSee('text-warning', escape: false);
        $this->blade('<x-api-docs.method-badge method="DELETE" />')->assertSee('text-error', escape: false);
    }

    #[Test]
    public function it_takes_a_tint_only_when_it_is_asked_for_one(): void
    {
        $this->blade('<x-api-docs.method-badge method="POST" tinted />')->assertSee('bg-success/10', escape: false);
        $this->blade('<x-api-docs.method-badge method="POST" />')->assertDontSee('bg-success/10', escape: false);
    }
}
