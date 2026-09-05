{{-- The line in the bar that says where you are. --}}
{{-- @var array<int, array{label: string, url?: string|null}> $items --}}
@props([
  'items' => [],
])

<nav {{ $attributes->class(['min-w-0 text-sm text-muted']) }} aria-label="{{ __('Breadcrumb') }}">
  @foreach ($items as $item)
    @unless ($loop->first)
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
    @endunless

    @if ($loop->last)
      <span class="text-ink" aria-current="page">{{ $item['label'] }}</span>
    @elseif (($item['url'] ?? null) !== null)
      <a href="{{ $item['url'] }}" data-turbo="true" class="transition-colors duration-150 hover:text-ink">{{ $item['label'] }}</a>
    @else
      {{ $item['label'] }}
    @endif
  @endforeach
</nav>
