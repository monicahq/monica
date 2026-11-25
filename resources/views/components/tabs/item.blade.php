{{-- One tab in an <x-tabs> row. --}}
{{--
  @var string|null $href
  @var bool $selected
  @var bool $action
  @var string|null $icon
  @var string|int|null $count
  @var string|null $countLabel
--}}
@props([
  'href' => null,
  'selected' => false,
  'action' => false,
  'icon' => null,
  'count' => null,
  'countLabel' => null,
])

@php
  $tag = $href ? 'a' : 'button';

  $base = 'group -mb-px flex h-9 flex-none cursor-pointer items-center gap-1.75 border-b-2 border-transparent px-2.5 '
    .'font-sans text-nav whitespace-nowrap text-muted no-underline '
    .'transition-colors duration-(--duration-fast) ease-out hover:text-ink '
    .'focus-ring '
    .'aria-selected:border-b-primary aria-selected:font-semibold aria-selected:text-ink';
@endphp

<{{ $tag }}
  @if ($href)
    href="{{ $href }}"
  @else
    type="button"
  @endif
  @unless ($action)
    role="tab"
    aria-selected="{{ $selected ? 'true' : 'false' }}"
  @endunless
  {{ $attributes->class([$base]) }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="15" :stroke-width="1.6" />
  @endif

  {{ $slot }}

  @if ($count !== null)
    <span
      class="flex h-4.75 min-w-4.75 items-center justify-center rounded-full px-1.25 text-2xs font-semibold text-muted group-aria-selected:bg-hover group-aria-selected:text-body"
      @if ($countLabel) title="{{ $countLabel }}" @endif
    >{{ $count }}</span>
  @endif
</{{ $tag }}>
