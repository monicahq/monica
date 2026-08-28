{{--
  A filter or a scope toggle: one of a short row of them, one of which is in force.

  What is chosen is a filled ink chip rather than a tinted outline, so which one is
  in force is never a matter of noticing that one border is slightly darker than the
  others. It also carries `aria-current` when it is a link and `aria-pressed` when
  it is a button, so it is not the fill alone that says so.

  It renders an anchor when it is given an href, since a filter that has a URL can be
  linked to, shared and reached by the back button. A chip that only changes
  something on the page is a button instead.

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
      'inline-flex h-9 cursor-pointer items-center gap-1.75 rounded-md border border-hairline-strong bg-canvas px-3.5',
      'font-sans text-sm font-semibold whitespace-nowrap text-ink-soft no-underline ',
      'transition-[background-color,transform] duration-(--duration-fast) ease-out',
      'hover:bg-sand active:translate-y-(--press-translate) ',
      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
      'aria-[current=true]:border-ink aria-[current=true]:bg-ink aria-[current=true]:text-canvas aria-[current=true]:shadow-none',
      'aria-[pressed=true]:border-ink aria-[pressed=true]:bg-ink aria-[pressed=true]:text-canvas aria-[pressed=true]:shadow-none',
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
