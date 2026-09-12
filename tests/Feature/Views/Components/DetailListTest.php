<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetailListTest extends TestCase
{
    #[Test]
    public function it_pairs_a_label_with_a_value(): void
    {
        $this->blade('<x-detail-list><x-detail-list.row label="Template">Dwight</x-detail-list.row></x-detail-list>')
            ->assertSee('<dl', escape: false)
            ->assertSee('<dt', escape: false)
            ->assertSee('Template', escape: false)
            ->assertSee('<dd', escape: false)
            ->assertSee('Dwight', escape: false);
    }

    #[Test]
    public function the_list_decides_how_wide_the_labels_are(): void
    {
        $this->blade('<x-detail-list label-width="minmax(0,170px)"><x-detail-list.row label="L">V</x-detail-list.row></x-detail-list>')
            ->assertSee('--detail-label-width: minmax(0,170px)', escape: false)
            ->assertSee('grid-cols-[var(--detail-label-width)_minmax(0,1fr)]', escape: false);
    }

    #[Test]
    public function a_row_takes_the_label_style_the_list_declares(): void
    {
        $this->blade('<x-detail-list label-style="caption"><x-detail-list.row label="Opened">Twice</x-detail-list.row></x-detail-list>')
            ->assertSee('tracking-caption', escape: false);

        $this->blade('<x-detail-list><x-detail-list.row label="Opened">Twice</x-detail-list.row></x-detail-list>')
            ->assertSee('text-sm text-muted', escape: false)
            ->assertDontSee('tracking-caption', escape: false);
    }

    #[Test]
    public function a_row_may_leave_the_value_unstyled_for_whatever_it_holds(): void
    {
        $rendered = $this->blade('<x-detail-list><x-detail-list.row label="L" value-class="">V</x-detail-list.row></x-detail-list>');

        $rendered->assertSee('m-0 min-w-0', escape: false);
        $rendered->assertDontSee('text-base text-pretty text-ink', escape: false);
    }

    #[Test]
    public function it_stacks_the_label_over_the_value_on_a_narrow_window(): void
    {
        $this->blade('<x-detail-list><x-detail-list.row label="L">V</x-detail-list.row></x-detail-list>')
            ->assertSee('max-[560px]:grid-cols-1', escape: false);
    }
}
