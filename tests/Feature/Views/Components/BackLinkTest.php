<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackLinkTest extends TestCase
{
    #[Test]
    public function it_points_back_and_says_where_to(): void
    {
        $this->blade('<x-back-link href="/settings">Back to settings</x-back-link>')
            ->assertSee('href="/settings"', escape: false)
            ->assertSee('Back to settings', escape: false);
    }

    #[Test]
    public function the_arrow_is_turned_round_so_it_points_the_way_out(): void
    {
        $this->blade('<x-back-link href="/settings">Back</x-back-link>')
            ->assertSee('rotate-180', escape: false);
    }

    #[Test]
    public function it_is_followed_without_a_full_page_load(): void
    {
        $this->blade('<x-back-link href="/settings">Back</x-back-link>')
            ->assertSee('data-turbo="true"', escape: false);
    }
}
