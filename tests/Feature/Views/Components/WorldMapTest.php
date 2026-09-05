<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorldMapTest extends TestCase
{
    #[Test]
    public function it_draws_the_world_in_the_box_the_projection_assumes(): void
    {
        $view = $this->blade('<x-world-map />');

        $view->assertSee('viewBox="0 0 1000 394.44"', escape: false);
        $view->assertSee('preserveAspectRatio="none"', escape: false);
    }

    #[Test]
    public function it_carries_the_land_it_was_generated_with(): void
    {
        $render = $this->blade('<x-world-map />')->__toString();

        $this->assertMatchesRegularExpression('/ d="M[\d.\-]+ [\d.\-]+l/', $render);
        $this->assertGreaterThan(50000, mb_strlen($render));
    }

    #[Test]
    public function it_paints_from_the_palette_rather_than_from_literal_colours(): void
    {
        $view = $this->blade('<x-world-map />');

        $view->assertSee('fill="var(--map-water)"', escape: false);
        $view->assertSee('fill="var(--map-land)"', escape: false);
        $view->assertSee('stroke="var(--map-graticule)"', escape: false);
        $view->assertSee('stroke="var(--map-coast)"', escape: false);
    }

    #[Test]
    public function it_names_no_colour_of_its_own(): void
    {
        $render = $this->blade('<x-world-map />')->__toString();

        $this->assertDoesNotMatchRegularExpression('/(fill|stroke)="#/', $render);
        $this->assertDoesNotMatchRegularExpression('/(fill|stroke)="rgb/', $render);
    }

    #[Test]
    public function it_rules_an_hour_of_the_world_every_fifteen_degrees(): void
    {
        $render = $this->blade('<x-world-map />')->__toString();

        $this->assertSame(25, preg_match_all('/<line\s+x1="([\d.]+)"\s+x2="\1"/', $render));
    }

    #[Test]
    public function it_puts_the_prime_meridian_and_the_equator_where_the_projection_puts_them(): void
    {
        $view = $this->blade('<x-world-map />');

        $view->assertSee('x1="500"', escape: false);
        $view->assertSee('y1="233.33"', escape: false);
    }

    #[Test]
    public function it_never_draws_a_line_across_the_map(): void
    {
        preg_match('/ d="([^"]+)"/', $this->blade('<x-world-map />')->__toString(), $found);

        $longest = 0.0;

        foreach (explode('Z', $found[1]) as $subpath) {
            if (! str_contains($subpath, 'l')) {
                continue;
            }

            foreach (explode(',', mb_substr($subpath, mb_strpos($subpath, 'l') + 1)) as $step) {
                $longest = max($longest, abs((float) explode(' ', trim($step))[0]));
            }
        }

        $this->assertLessThan(100.0, $longest);
    }

    #[Test]
    public function it_is_hidden_from_a_screen_reader_because_the_words_beside_it_carry_the_answer(): void
    {
        $view = $this->blade('<x-world-map />');

        $view->assertSee('aria-hidden="true"', escape: false);
        $view->assertSee('focusable="false"', escape: false);
    }
}
