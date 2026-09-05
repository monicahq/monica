{{-- The tile a section is recognised by: one glyph on a ground tinted from the section's colour. --}}
{{--
  @var string|null $icon
  @var string|null $colour
  @var int $size
--}}
@props([
  'icon' => null,
  'colour' => null,
  'size' => 28,
])

@php
  use App\Enums\VaultColourEnum;
  use App\Models\Section;

  $glyph = in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON;

  $tint = VaultColourEnum::tintFor($colour);
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-md border', $tint]) }}
  style="width: {{ $size }}px; height: {{ $size }}px"
  aria-hidden="true"
>
  <x-icon :name="$glyph" :size="(int) round($size * 0.54)" :stroke-width="1.5" />
</span>
