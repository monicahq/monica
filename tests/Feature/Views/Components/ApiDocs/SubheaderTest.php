<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubheaderTest extends TestCase
{
    #[Test]
    public function it_offers_the_way_to_get_a_key(): void
    {
        $this->blade('<x-api-docs.subheader />')
            ->assertSee(route('settings.security.index'), escape: false);
    }
}
