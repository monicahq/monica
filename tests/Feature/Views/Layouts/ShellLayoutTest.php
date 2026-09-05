<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShellLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_drops_its_bar_on_paper_since_the_bar_is_neither_fixed_nor_sticky(): void
    {
        $this->actingAs(User::factory()->create())
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('bg-topbar px-4 sm:px-6 print:hidden', escape: false);
    }

    #[Test]
    public function it_gives_the_reading_column_the_whole_sheet_on_paper(): void
    {
        $this->actingAs(User::factory()->create())
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('print:max-w-none print:p-0', escape: false);
    }

    #[Test]
    public function it_lets_the_page_run_past_the_first_sheet_rather_than_scrolling_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('print:block print:h-auto print:overflow-visible', escape: false)
            ->assertSee('overflow-y-auto print:overflow-visible', escape: false);
    }
}
