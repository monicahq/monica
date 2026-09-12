{{-- The way around the reference. --}}
{{--
  @var array $navigation
  @var string $current
--}}
@props(['navigation', 'current'])

@php
  $entry = 'rounded-md px-2.5 py-1.75 text-[14px] transition-colors duration-(--duration-fast) ease-out focus-ring';
@endphp

<aside class="hidden w-68 shrink-0 self-start border-r border-hairline px-4 py-6 lg:sticky lg:top-28 lg:block lg:h-[calc(100vh-7rem)] lg:overflow-y-auto">
  <h2 class="mb-2 ml-2.5 text-[11px] font-medium tracking-caption text-muted-soft uppercase">Getting started</h2>

  <nav class="mb-6.5 flex flex-col gap-px" aria-label="Getting started">
    @foreach ($navigation['guides'] as $guide)
      <a
        href="{{ route('docs.api.show', ['section' => $guide['id']]) }}"
        data-turbo="true"
        x-show="!query || {{ Js::from(mb_strtolower($guide['label'])) }}.includes(query.toLowerCase())"
        @if ($current === $guide['id']) aria-current="page" @endif
        class="{{ $entry }} {{ $current === $guide['id'] ? 'bg-hover font-medium text-ink' : 'text-body hover:bg-hover hover:text-ink' }}"
      >{{ $guide['label'] }}</a>
    @endforeach
  </nav>

  <h2 class="mb-2 ml-2.5 text-[11px] font-medium tracking-caption text-muted-soft uppercase">Resources</h2>

  @foreach ($navigation['resources'] as $group)
    <div
      class="mb-4.5"
      x-show="{{ collect($group['items'])->map(fn (array $item): string => '!query || '.Js::from(mb_strtolower($item['label'].' '.$item['method'])).'.includes(query.toLowerCase())')->implode(' || ') }}"
    >
      <h3 class="mb-1 ml-2.5 text-card-title text-ink">{{ $group['name'] }}</h3>

      <nav class="ml-2.5 flex flex-col gap-px border-l border-hairline-soft" aria-label="{{ $group['name'] }}">
        @foreach ($group['items'] as $item)
          <a
            href="{{ route('docs.api.show', ['section' => $item['id']]) }}"
            data-turbo="true"
            x-show="!query || {{ Js::from(mb_strtolower($item['label'].' '.$item['method'])) }}.includes(query.toLowerCase())"
            @if ($current === $item['id']) aria-current="page" @endif
            class="grid grid-cols-[3.5rem_1fr] items-center gap-2 {{ $entry }} {{ $current === $item['id'] ? 'bg-canvas font-medium text-ink shadow-[inset_0_0_0_1px_var(--hairline-strong)]' : 'text-body hover:bg-hover hover:text-ink' }}"
          >
            <x-api-docs.method-badge :method="$item['method']" />
            <span class="truncate">{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
    </div>
  @endforeach
</aside>
