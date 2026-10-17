<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    private const CALL = '<x-pagination :paginator="$paginator" :size="$size" :default="20" />';

    /** @return LengthAwarePaginator<int, int> */
    private function paginator(int $total, int $perPage, int $currentPage): LengthAwarePaginator
    {
        $first = ($currentPage - 1) * $perPage + 1;

        return new LengthAwarePaginator(
            items: $total === 0 ? [] : range($first, min($first + $perPage - 1, $total)),
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
            options: ['path' => '/people'],
        );
    }

    #[Test]
    public function it_renders_nothing_for_an_empty_list(): void
    {
        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(0, 20, 1), 'size' => 20]);

        $rendered->assertDontSee(__('Pagination'), escape: false);
        $rendered->assertDontSee(__('Rows per page'), escape: false);
    }

    #[Test]
    public function it_says_which_rows_are_shown_out_of_how_many(): void
    {
        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 3), 'size' => 20]);

        $rendered->assertSee('41 to 60 of 120', escape: false);
    }

    #[Test]
    public function it_marks_the_current_page_and_links_the_others(): void
    {
        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 3), 'size' => 20]);

        $rendered->assertSee('aria-current="page"', escape: false);
        $rendered->assertSee('rel="prev"', escape: false);
        $rendered->assertSee('rel="next"', escape: false);
        $rendered->assertSee('/people?page=4', escape: false);
        $rendered->assertSee(__('Page :page', ['page' => 4]), escape: false);
        $rendered->assertDontSee('aria-disabled="true"', escape: false);
    }

    #[Test]
    public function it_keeps_a_disabled_end_on_screen(): void
    {
        $first = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 1), 'size' => 20]);
        $first->assertSee('aria-disabled="true"', escape: false);
        $first->assertDontSee('rel="prev"', escape: false);
        $first->assertSee(__('pagination.previous'), escape: false);

        $last = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 6), 'size' => 20]);
        $last->assertDontSee('rel="next"', escape: false);
        $last->assertSee(__('pagination.next'), escape: false);
    }

    #[Test]
    public function it_says_nothing_about_the_rows_of_a_page_past_the_end(): void
    {
        $rendered = $this->blade(self::CALL, [
            'paginator' => new LengthAwarePaginator(items: [], total: 120, perPage: 20, currentPage: 9, options: ['path' => '/people']),
            'size' => 20,
        ]);

        $rendered->assertDontSee(' of 120', escape: false);
        $rendered->assertSee('rel="prev"', escape: false);
        $rendered->assertSee(__('Rows per page'), escape: false);
    }

    #[Test]
    public function it_offers_the_size_field_even_when_everything_fits_on_one_page(): void
    {
        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(12, 20, 1), 'size' => 20]);

        $rendered->assertDontSee('aria-current="page"', escape: false);
        $rendered->assertSee(__('Rows per page'), escape: false);
    }

    #[Test]
    public function it_lists_every_size_and_selects_the_one_in_force(): void
    {
        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 1), 'size' => 20]);

        $rendered->assertSee('name="size"', escape: false);
        $rendered->assertSeeInOrder(['value="10"', 'value="20" selected', 'value="50"', 'value="100"', 'value="500"', 'value="all"'], escape: false);
        $rendered->assertSee(__('All'), escape: false);

        $this->blade(self::CALL, ['paginator' => $this->paginator(120, 120, 1), 'size' => 'all'])
            ->assertSee('value="all" selected', escape: false);
    }

    #[Test]
    public function it_carries_the_other_query_values_and_never_the_page(): void
    {
        $this->app['request']->merge(['query' => 'dwight', 'page' => 3, 'size' => 20]);

        $rendered = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 3), 'size' => 20]);

        $rendered->assertSee('name="query" value="dwight"', escape: false);
        $rendered->assertDontSee('name="page"', escape: false);
        $rendered->assertDontSee('name="size" value', escape: false);
    }

    #[Test]
    public function it_pushes_to_a_target_only_when_given_one(): void
    {
        $plain = $this->blade(self::CALL, ['paginator' => $this->paginator(120, 20, 3), 'size' => 20]);
        $plain->assertDontSee('x-target.push', escape: false);

        $targeted = $this->blade(
            '<x-pagination :paginator="$paginator" :size="20" :default="20" target="contacts" />',
            ['paginator' => $this->paginator(120, 20, 3)],
        );
        $targeted->assertSee('x-target.push="contacts"', escape: false);
    }
}
