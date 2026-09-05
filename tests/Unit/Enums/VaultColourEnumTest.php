<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\VaultColourEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultColourEnumTest extends TestCase
{
    /**
     * The grounds a mark drawn in one of these colours can land on: the page and
     * a panel, in each theme. The tint is an alpha, so the ground is what the
     * twelfth of the hue composites over, and the ratio differs on each.
     *
     * @var array<string, array{ground: string, theme: string}>
     */
    private const array GROUNDS = [
        'light canvas' => ['ground' => '#ffffff', 'theme' => 'light'],
        'light card' => ['ground' => '#fafafa', 'theme' => 'light'],
        'dark canvas' => ['ground' => '#09090b', 'theme' => 'dark'],
        'dark card' => ['ground' => '#18181b', 'theme' => 'dark'],
    ];

    #[Test]
    public function it_names_every_colour(): void
    {
        foreach (VaultColourEnum::cases() as $colour) {
            $this->assertNotEmpty($colour->label());
        }
    }

    #[Test]
    public function it_offers_eight_colours_beside_the_neutral_default(): void
    {
        $this->assertCount(8, VaultColourEnum::cases());
        $this->assertNotEmpty(VaultColourEnum::neutralTint());
    }

    #[Test]
    public function it_draws_every_colour_at_or_above_the_contrast_a_mark_needs(): void
    {
        foreach (VaultColourEnum::cases() as $colour) {
            $pair = $colour->measurable();

            foreach (self::GROUNDS as $where => $ground) {
                $background = $this->composite($pair['hue'], $ground['ground'], $pair['alpha']);
                $foreground = $ground['theme'] === 'light' ? $pair['light'] : $pair['dark'];

                $this->assertGreaterThanOrEqual(
                    4.5,
                    $this->contrast($foreground, $background),
                    "{$colour->label()} on {$where} is below AA.",
                );
            }
        }
    }

    #[Test]
    public function it_draws_the_neutral_pair_above_the_contrast_a_mark_needs(): void
    {
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('#52525b', '#fafafa'));
        $this->assertGreaterThanOrEqual(4.5, $this->contrast('#d4d4d8', '#18181b'));
    }

    #[Test]
    public function it_gives_every_colour_a_tint_naming_its_own_border(): void
    {
        foreach (VaultColourEnum::cases() as $colour) {
            $this->assertStringContainsString('border-', $colour->tint());
            $this->assertStringContainsString('dark:text-', $colour->tint());
        }

        $this->assertStringContainsString('border-', VaultColourEnum::neutralTint());
    }

    #[Test]
    public function it_falls_back_to_neutral_for_a_colour_it_does_not_offer(): void
    {
        $this->assertEquals(VaultColourEnum::neutralTint(), VaultColourEnum::tintFor(null));
        $this->assertEquals(VaultColourEnum::neutralTint(), VaultColourEnum::tintFor(''));
        $this->assertEquals(VaultColourEnum::neutralTint(), VaultColourEnum::tintFor('#ff0000'));
        $this->assertEquals(VaultColourEnum::Green->tint(), VaultColourEnum::tintFor('#4f8a49'));
    }

    /**
     * What a hue drawn at a fraction of itself over a ground actually resolves
     * to, which is the colour the glyph is measured against.
     */
    private function composite(string $hue, string $ground, float $alpha): string
    {
        [$hr, $hg, $hb] = $this->channels($hue);
        [$gr, $gg, $gb] = $this->channels($ground);

        return sprintf(
            '#%02x%02x%02x',
            (int) round($alpha * $hr + (1 - $alpha) * $gr),
            (int) round($alpha * $hg + (1 - $alpha) * $gg),
            (int) round($alpha * $hb + (1 - $alpha) * $gb),
        );
    }

    /**
     * WCAG 2.2 contrast, which is the ratio of the two relative luminances with
     * a fifth of a twentieth added to each so that black against black is 1 and
     * not a division by zero.
     */
    private function contrast(string $one, string $other): float
    {
        $first = $this->luminance($one);
        $second = $this->luminance($other);

        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }

    private function luminance(string $colour): float
    {
        $linear = array_map(function (int $channel): float {
            $proportion = $channel / 255;

            return $proportion <= 0.04045
                ? $proportion / 12.92
                : (($proportion + 0.055) / 1.055) ** 2.4;
        }, $this->channels($colour));

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * @return array{int, int, int}
     */
    private function channels(string $colour): array
    {
        $hex = ltrim($colour, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
