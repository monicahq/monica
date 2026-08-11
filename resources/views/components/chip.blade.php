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
  {{ $attributes->class(['erica-chip']) }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="16" :stroke-width="1.5" />
  @endif

  {{ $slot }}

  @if ($count !== null)
    <span class="erica-chip__count">{{ $count }}</span>
  @endif
</{{ $tag }}>
