<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubheaderTest extends TestCase
{
    #[Test]
    public function it_says_which_version_of_the_api_this_is(): void
    {
        $this->blade('<x-api-docs.subheader />')->assertSee('v1');
    }

    #[Test]
    public function the_search_box_carries_a_real_label(): void
    {
        $this->blade('<x-api-docs.subheader />')->assertSee('sr-only', escape: false);
    }

    #[Test]
    public function it_offers_the_way_to_get_a_key(): void
    {
        $this->blade('<x-api-docs.subheader />')
            ->assertSee('Get an API key')
            ->assertSee(route('settings.security.index'), escape: false);
    }
}
