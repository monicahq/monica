{{--
  The line in the bar that says where you are. Every screen that has one draws
  it here rather than writing the markup out again, since a trail that is a
  different size in one corner of the product than in another reads as two
  products.

  It is body size rather than the smaller metadata size: the trail is something
  you read and click, not a hint in the corner. The last item is where you are
  and is never a link; the ones before it lead back, and a step with no url is a
  word rather than a link, for a section that has no screen of its own.

  Usage:
    <x-breadcrumb :items="[
      ['label' => __('Settings')],
      ['label' => __('Templates'), 'url' => route('settings.templates.index')],
      ['label' => $template->name],
    ]" />

  @var array<int, array{label: string, url?: string|null}> $items
--}}
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
      <a href="{{ $item['url'] }}" data-turbo="true" class="transition-colors duration-150 hover:text-brand">{{ $item['label'] }}</a>
    @else
      {{ $item['label'] }}
    @endif
  @endforeach
</nav>
