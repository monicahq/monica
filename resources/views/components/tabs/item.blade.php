{{--
  One tab in an <x-tabs> row.

  It renders an anchor when it is given an href, since a tab that has a URL can be
  linked to, shared and reached by the back button. A tab that only opens something
  on the page, which is what the one that adds a page is, is a button instead.

  `count` is the number of things behind the tab, drawn as a small round mark after
  the label. It takes a ground only on the tab you are on: a row of filled marks
  would compete with the rule for saying which tab that is. `countLabel` is what the
  mark is called for somebody who cannot see that it is a count, and a caller
  passing `count` without it is passing a number nobody can read.

  `action` is for the one thing in the row that is not a destination, which is the
  control that adds another tab. It is drawn like a tab because it sits on the same
  rule, and it is not announced as one, because a tab that selects nothing is a lie
  told to whoever is listening rather than looking.

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

  $base = '-mb-px flex h-9 flex-none cursor-pointer items-center gap-1.75 border-b-2 border-transparent px-2.5 '
    .'font-sans text-nav whitespace-nowrap text-muted no-underline '
    .'transition-[color,border-color] duration-(--duration-fast) ease-out hover:text-ink '
    .'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none '
    .'aria-[selected=true]:border-b-primary aria-[selected=true]:font-semibold aria-[selected=true]:text-ink';

  $mark = $selected ? 'bg-hover text-body' : 'bg-transparent text-muted-soft';
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
      class="flex h-4.75 min-w-4.75 items-center justify-center rounded-full px-1.25 text-[11px] font-semibold {{ $mark }}"
      @if ($countLabel) title="{{ $countLabel }}" @endif
    >{{ $count }}</span>
  @endif
</{{ $tag }}>
