{{-- What a list shows when it holds nothing. --}}
{{--
  @var string|null $title
  @var string|null $href
  @var string|null $linkLabel
  @var \Illuminate\View\ComponentSlot|null $action
--}}
@props([
  'title' => null,
  'href' => null,
  'linkLabel' => null,
])

<div {{ $attributes->class(['flex min-h-60 flex-col items-center justify-center gap-3.5 px-8 py-12 text-center']) }}>
  @isset($icon)
    <span class="flex size-16 -rotate-4 items-center justify-center rounded-lg border border-hairline bg-card text-body" aria-hidden="true">
      {{ $icon }}
    </span>
  @endisset

  @isset($title)
    <p class="text-section-title text-ink">{{ $title }}</p>
  @endisset

  @if (trim($slot->toHtml()) !== '')
    <p class="max-w-reading-narrow text-base text-pretty text-muted">{{ $slot }}</p>
  @endif

  @isset($action)
    <div class="mt-1">{{ $action }}</div>
  @endisset

  @if ($href && $linkLabel)
    <x-link :href="$href">{{ $linkLabel }}</x-link>
  @endif
</div>
