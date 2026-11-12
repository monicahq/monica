<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackLinkTest extends TestCase
{
    #[Test]
    public function it_is_followed_without_a_full_page_load(): void
    {
        $this->blade('<x-back-link href="/settings">Back</x-back-link>')
            ->assertSee('data-turbo="true"', escape: false);
    }
}
