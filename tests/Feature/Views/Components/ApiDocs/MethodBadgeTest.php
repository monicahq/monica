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
}
