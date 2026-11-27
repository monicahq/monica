<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchableListTest extends TestCase
{
    #[Test]
    public function it_is_a_button_unfolding_a_searchable_list_in_named_groups(): void
    {
        $rendered = $this->blade(
            '<x-searchable-list id="branch" label-id="branch-label" search-label="Search the branches">Scranton<x-slot:trigger>Scranton</x-slot:trigger><x-slot:option><span x-text="option.label"></span></x-slot:option></x-searchable-list>',
        );

        $rendered->assertSee('aria-haspopup="listbox"', escape: false);
        $rendered->assertSee(':aria-expanded="open ? \'true\' : \'false\'"', escape: false);
        $rendered->assertSee('aria-labelledby="branch-label branch"', escape: false);
        $rendered->assertSee('role="combobox"', escape: false);
        $rendered->assertSee('aria-controls="branch-listbox"', escape: false);
        $rendered->assertSee(':aria-activedescendant="active === \'\' ? null : active"', escape: false);
        $rendered->assertSee('aria-label="Search the branches"', escape: false);
        $rendered->assertSee('id="branch-listbox" role="listbox"', escape: false);
        $rendered->assertSee(':role="labels[group.key] ? \'group\' : \'none\'"', escape: false);
        $rendered->assertSee('role="option"', escape: false);
    }

    #[Test]
    public function it_points_the_button_at_the_error_without_a_state_its_role_does_not_carry(): void
    {
        $rendered = $this->blade(
            '<x-searchable-list id="branch" label-id="branch-label" search-label="Search the branches" :invalid="true" described-by="branch-error">Scranton<x-slot:trigger>Scranton</x-slot:trigger><x-slot:option>Stamford</x-slot:option></x-searchable-list>',
        );

        $rendered->assertSee('aria-describedby="branch-error"', escape: false);
        $rendered->assertDontSee('aria-invalid', escape: false);
    }
}
