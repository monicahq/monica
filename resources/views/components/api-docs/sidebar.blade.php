{{-- The way around the reference. --}}
{{--
  @var array $navigation
  @var string $current
--}}
@props(['navigation', 'current'])

@php
  $entry = 'rounded-md px-2.5 py-1.75 text-[14px] transition-colors duration-(--duration-fast) ease-out focus-ring';
@endphp

<aside
  x-data="apiDocsSidebarScroll"
  @click="remember()" @keydown.enter="remember()"
  class="hidden w-68 shrink-0 self-start border-r border-hairline px-4 py-6 lg:block"
>
  <h2 class="mb-2 ml-2.5 text-2xs font-medium tracking-caption text-muted-soft uppercase">Getting started</h2>

  <nav class="mb-6.5 flex flex-col gap-px" aria-label="Getting started">
    @foreach ($navigation['guides'] as $guide)
      <a
        href="{{ $guide['url'] }}"
        data-turbo="true"
        x-show="!query || {{ Js::from(mb_strtolower($guide['label'])) }}.includes(query.toLowerCase())"
        @if ($current === $guide['id']) aria-current="page" @endif
        class="{{ $entry }} {{ $current === $guide['id'] ? 'bg-hover font-medium text-ink' : 'text-body hover:bg-hover hover:text-ink' }}"
      >{{ $guide['label'] }}</a>
    @endforeach
  </nav>

  <h2 class="mb-2 ml-2.5 text-2xs font-medium tracking-caption text-muted-soft uppercase">Resources</h2>

  @foreach ($navigation['resources'] as $group)
    <div
      class="mb-4.5"
      x-show="{{ collect($group['items'])->map(fn (array $item): string => '!query || '.Js::from(mb_strtolower($item['label'].' '.implode(' ', $item['methods']))).'.includes(query.toLowerCase())')->implode(' || ') }}"
    >
      <h3 class="mb-1 ml-2.5 text-card-title text-ink">{{ $group['name'] }}</h3>

      <nav class="ml-2.5 flex flex-col gap-px border-l border-hairline-soft" aria-label="{{ $group['name'] }}">
        @foreach ($group['items'] as $item)
          <a
            href="{{ route('docs.api.show', ['section' => $item['id']]) }}"
            data-turbo="true"
            x-show="!query || {{ Js::from(mb_strtolower($item['label'].' '.implode(' ', $item['methods']))) }}.includes(query.toLowerCase())"
            @if ($current === $item['id']) aria-current="page" @endif
            class="grid grid-cols-[3.5rem_1fr] items-center gap-2 {{ $entry }} {{ $current === $item['id'] ? 'bg-canvas font-medium text-ink inset-ring inset-ring-hairline-strong' : 'text-body hover:bg-hover hover:text-ink' }}"
          >
            <span class="flex flex-col">
              @foreach ($item['methods'] as $method)
                <x-api-docs.method-badge :method="$method" />
              @endforeach
            </span>
            <span class="truncate">{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
    </div>
  @endforeach
</aside>
