{{-- A filter or a scope toggle: one of a short row of them, one of which is in force. --}}
{{--
  @var string|null $href
  @var bool $selected
  @var string|null $icon
  @var string|int|null $count
--}}
@props([
  'href' => null,
  'selected' => false,
  'icon' => null,
  'count' => null,
])

@php
  $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
  @if ($href)
    href="{{ $href }}"
    @if ($selected) aria-current="true" @endif
  @else
    type="button"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
  @endif
  {{
    $attributes->class([
      'inline-flex h-8 cursor-pointer items-center gap-1.75 rounded-full border border-hairline bg-canvas px-3.25',
      'font-sans text-sm font-medium whitespace-nowrap text-body no-underline ',
      'transition-[background-color,border-color,box-shadow] duration-(--duration-fast) ease-out',
      'hover:bg-hover hover:text-ink ',
      'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none',
      'aria-[current=true]:border-primary aria-[current=true]:bg-primary aria-[current=true]:text-on-primary',
      'aria-[pressed=true]:border-primary aria-[pressed=true]:bg-primary aria-[pressed=true]:text-on-primary',
    ])
  }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="16" :stroke-width="1.5" />
  @endif

  {{ $slot }}

  @if ($count !== null)
    <span class="font-mono text-[12px] opacity-70">{{ $count }}</span>
  @endif
</{{ $tag }}>
