{{-- The column of links beside a group of screens, which slides in as a drawer on a narrow window. --}}
{{--
  @var list<array{title?: string, entries: list<array{key: string, label: string, icon: string, url: string, currentIsDescendant?: bool, count?: int, countLabel?: string}>}> $groups
  @var string $current
  @var string|null $leaveUrl
  @var string|null $leaveLabel
  @var string $label
--}}
@props([
  'groups',
  'current',
  'leaveUrl' => null,
  'leaveLabel' => null,
  'label',
])

@php
  $item = 'flex min-h-8 items-center gap-2.5 rounded-md px-2 py-1.5 text-nav transition duration-(--duration-fast) ease-out max-lg:min-h-10';
@endphp

<x-nav-drawer.container :product-link="$leaveUrl !== null">
  @if ($leaveUrl !== null)
    <div class="mx-2 mb-1.5">
      <a
        href="{{ $leaveUrl }}"
        data-turbo="true"
        class="flex items-center gap-2.5 rounded-md border border-hairline bg-canvas px-2.5 py-2 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
      >
        <x-icon name="arrow-right" :size="14" :stroke-width="1.5" class="shrink-0 rotate-180 text-muted" />
        {{ $leaveLabel }}
      </a>
    </div>
  @endif

  <nav aria-label="{{ $label }}" class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    @foreach ($groups as $group)
      @isset($group['title'])
        <h2 class="px-2 pt-4 pb-2.5 text-2xs font-medium tracking-caption text-muted-soft uppercase first:pt-1">{{ $group['title'] }}</h2>
      @endisset

      @foreach ($group['entries'] as $entry)
        <a
          href="{{ $entry['url'] }}"
          data-turbo="true"
          @if ($current === $entry['key']) aria-current="{{ ($entry['currentIsDescendant'] ?? false) ? 'true' : 'page' }}" @endif
          class="{{ $item }} {{ $current === $entry['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }} focus-ring"
        >
          <x-icon :name="$entry['icon']" :size="16" :stroke-width="1.5" />
          <span class="flex-1">{{ $entry['label'] }}</span>

          @if (($entry['count'] ?? 0) > 0)
            <span class="text-2xs text-muted tabular-nums" aria-hidden="true">{{ $entry['count'] }}</span>
            <span class="sr-only">{{ $entry['countLabel'] ?? '' }}</span>
          @endif
        </a>
      @endforeach
    @endforeach
  </nav>
</x-nav-drawer.container>
