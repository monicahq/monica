{{-- The line in the bar that says where you are. --}}
{{-- @var array<int, array{label: string, url?: string|null, icon?: string|null}> $items --}}
@props([
  'items' => [],
])

<nav {{ $attributes->class(['flex min-w-0 flex-wrap items-center gap-x-1 gap-y-0.5 text-sm text-muted']) }} aria-label="{{ __('Breadcrumb') }}">
  @foreach ($items as $item)
    @unless ($loop->first)
      <x-icon name="chevron-right" :size="13" :stroke-width="1.6" class="text-placeholder" />
    @endunless

    @if (! $loop->last && ($item['url'] ?? null) !== null)
      <a
        href="{{ $item['url'] }}"
        data-turbo="true"
        class="flex items-center gap-1.5 rounded-xs transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring"
      >
        @isset($item['icon'])
          <x-icon :name="$item['icon']" :size="14" :stroke-width="1.5" />
        @endisset

        {{ $item['label'] }}
      </a>
    @else
      <span
        @class(['flex items-center gap-1.5', 'text-ink' => $loop->last])
        @if ($loop->last) aria-current="page" @endif
      >
        @isset($item['icon'])
          <x-icon :name="$item['icon']" :size="14" :stroke-width="1.5" />
        @endisset

        {{ $item['label'] }}
      </span>
    @endif
  @endforeach
</nav>
