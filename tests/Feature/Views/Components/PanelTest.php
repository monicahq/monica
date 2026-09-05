<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\VaultColourEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelTest extends TestCase
{
    #[Test]
    public function it_draws_no_header_when_it_has_no_title(): void
    {
        $rendered = $this->blade('<x-panel>Anything</x-panel>');

        $rendered->assertSee('rounded-lg border shadow-panel', escape: false);
        $rendered->assertSee('border-hairline bg-canvas', escape: false);
        $rendered->assertSee('Anything', escape: false);
        $rendered->assertDontSee('<header', escape: false);
    }

    #[Test]
    public function it_names_itself_and_marks_itself_when_it_is_given_a_title(): void
    {
        $rendered = $this->blade('<x-panel title="This vault" subtitle="Six of them" icon="vault">Rows</x-panel>');

        $rendered->assertSee('<h2 class="text-card-title text-ink">This vault</h2>', escape: false);
        $rendered->assertSee('Six of them', escape: false);
        $rendered->assertSee('rounded-md border border-hairline bg-card text-body', escape: false);
    }

    #[Test]
    public function it_rules_off_its_header_and_tints_the_strip_its_footer_sits_in(): void
    {
        $rendered = $this->blade('<x-panel title="This vault">Rows<x-slot:footer>Save it</x-slot:footer></x-panel>');

        $rendered->assertSee('border-b px-4 py-3.5 border-hairline', escape: false);
        $rendered->assertDontSee('border-b px-4 py-3.5 border-hairline bg-', escape: false);
        $rendered->assertSee('border-t border-hairline bg-card px-4 py-3', escape: false);
    }

    #[Test]
    public function the_panel_that_ends_something_says_so_before_it_is_read(): void
    {
        $rendered = $this->blade('<x-panel title="Delete this vault" icon="vault" tone="danger">Gone</x-panel>');

        $rendered->assertSee('border-error-edge bg-canvas', escape: false);
        $rendered->assertSee('border-error-edge bg-error-tint', escape: false);
        $rendered->assertSee('rounded-md border border-transparent bg-error text-white', escape: false);
        $rendered->assertSee('<h2 class="text-card-title text-error">Delete this vault</h2>', escape: false);
    }

    #[Test]
    public function it_centres_the_parts_of_a_header_whose_title_is_one_line(): void
    {
        $this->blade('<x-panel title="Contact information" icon="document"><p>Body</p></x-panel>')
            ->assertSee('min-h-12 items-center', escape: false)
            ->assertDontSee('items-start', escape: false);
    }

    #[Test]
    public function it_keeps_the_mark_level_with_a_title_that_has_a_subtitle_under_it(): void
    {
        $this->blade('<x-panel title="Picture" subtitle="A photograph helps." icon="profile"><p>Body</p></x-panel>')
            ->assertSee('min-h-12 items-start', escape: false)
            ->assertSee('A photograph helps.');
    }

    #[Test]
    public function it_takes_the_padding_off_its_body_for_a_list_of_rows(): void
    {
        $this->blade('<x-panel>Prose</x-panel>')
            ->assertSee('<div class="p-4">', escape: false);

        $this->blade('<x-panel :flush="true">Rows</x-panel>')
            ->assertDontSee('<div class="p-4">', escape: false);
    }

    #[Test]
    public function it_tints_its_mark_when_a_tint_is_handed_in(): void
    {
        $this->blade('<x-panel title="Health" icon="health" :tint="$tint">Body</x-panel>', [
            'tint' => VaultColourEnum::Green->tint(),
        ])->assertSee(VaultColourEnum::Green->tint(), escape: false);
    }

    #[Test]
    public function it_leaves_its_mark_untinted_when_none_is_handed_in(): void
    {
        $this->blade('<x-panel title="Health" icon="health">Body</x-panel>')
            ->assertSee('bg-card text-body', escape: false);
    }

    #[Test]
    public function it_keeps_the_danger_mark_red_whatever_tint_is_handed_in(): void
    {
        $rendered = $this->blade('<x-panel title="Delete" icon="lock" tone="danger" :tint="$tint">Body</x-panel>', [
            'tint' => VaultColourEnum::Green->tint(),
        ])->__toString();

        $this->assertStringContainsString('bg-error', $rendered);
        $this->assertStringNotContainsString('bg-[#4f8a49]/12', $rendered);
    }
}
