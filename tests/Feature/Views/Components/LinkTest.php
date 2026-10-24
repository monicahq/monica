<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LinkTest extends TestCase
{
    #[Test]
    public function it_goes_through_turbo_by_default(): void
    {
        $this->blade('<x-link href="/vaults/dunder-mifflin">Dunder Mifflin</x-link>')
            ->assertSee('data-turbo="true"', escape: false);
    }

    #[Test]
    public function it_leaves_the_page_change_to_the_browser_when_asked(): void
    {
        $this->blade('<x-link href="/settings/account/billing/invoices/1" data-turbo="false">Invoice</x-link>')
            ->assertSee('data-turbo="false"', escape: false)
            ->assertDontSee('data-turbo="true"', escape: false);

        $this->blade('<x-link href="/vaults" :turbo="false">Vaults</x-link>')
            ->assertDontSee('data-turbo', escape: false);
    }

    #[Test]
    public function it_leaves_a_link_alpine_ajax_handles_to_alpine_ajax(): void
    {
        $this->blade('<x-link href="/vaults?page=2" x-target="rows">Load more</x-link>')
            ->assertDontSee('data-turbo', escape: false);
    }
}
