{{--
  The way around the reference.

  Every entry is a link to a page of its own, so the reference behaves like a
  set of documents rather than one long one: a link opens what it names, the
  back button walks between endpoints, and none of it depends on the scripting.
  The script only narrows the list as somebody types.

  Which entry is the one being read is decided here rather than in the browser,
  because the server already knows.

  @var array $navigation
  @var string $current
--}}
@props(['navigation', 'current'])

@php
  $entry = 'rounded-md px-2.5 py-1.75 text-[14px] transition-colors duration-(--duration-fast) ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
@endphp

<aside class="hidden w-68 shrink-0 self-start border-r border-hairline px-4 py-6 lg:sticky lg:top-27 lg:block lg:h-[calc(100vh-6.75rem)] lg:overflow-y-auto">
  <h2 class="mb-2 ml-2.5 text-[12px] font-semibold tracking-[0.09em] text-muted uppercase">Getting started</h2>

  <nav class="mb-6.5 flex flex-col gap-px" aria-label="Getting started">
    @foreach ($navigation['guides'] as $guide)
      <a
        href="{{ route('docs.api.show', ['section' => $guide['id']]) }}"
        data-turbo="true"
        x-show="!query || {{ Js::from(mb_strtolower($guide['label'])) }}.includes(query.toLowerCase())"
        @if ($current === $guide['id']) aria-current="page" @endif
        class="{{ $entry }} {{ $current === $guide['id'] ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover hover:text-ink' }}"
      >{{ $guide['label'] }}</a>
    @endforeach
  </nav>

  <h2 class="mb-2 ml-2.5 text-[12px] font-semibold tracking-[0.09em] text-muted uppercase">Resources</h2>

  @foreach ($navigation['resources'] as $group)
    <div
      class="mb-4.5"
      x-show="{{ collect($group['items'])->map(fn (array $item): string => '!query || '.Js::from(mb_strtolower($item['label'].' '.$item['method'])).'.includes(query.toLowerCase())')->implode(' || ') }}"
    >
      <h3 class="mb-1 ml-2.5 text-[14px] font-semibold text-ink">{{ $group['name'] }}</h3>

      <nav class="ml-2.5 flex flex-col gap-px border-l border-hairline-soft" aria-label="{{ $group['name'] }}">
        @foreach ($group['items'] as $item)
          <a
            href="{{ route('docs.api.show', ['section' => $item['id']]) }}"
            data-turbo="true"
            x-show="!query || {{ Js::from(mb_strtolower($item['label'].' '.$item['method'])) }}.includes(query.toLowerCase())"
            @if ($current === $item['id']) aria-current="page" @endif
            class="grid grid-cols-[3.5rem_1fr] items-center gap-2 {{ $entry }} {{ $current === $item['id'] ? 'bg-canvas font-semibold text-ink shadow-[inset_0_0_0_1px_var(--hairline-strong)]' : 'text-body hover:bg-hover hover:text-ink' }}"
          >
            <x-api-docs.method-badge :method="$item['method']" />
            <span class="truncate">{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
    </div>
  @endforeach
</aside>
