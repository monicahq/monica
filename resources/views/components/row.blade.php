{{-- One line of a list: a coloured mark, a name, a sentence, and whatever belongs at the far end. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var string|null $icon
  @var string|null $meta
  @var string|null $metaSub
  @var string|null $href
  @var \Illuminate\View\ComponentSlot|null $leading
  @var \Illuminate\View\ComponentSlot|null $end
--}}
@props([
  'title' => null,
  'description' => null,
  'icon' => null,
  'meta' => null,
  'metaSub' => null,
  'href' => null,
])

@php
  $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
  @if ($href) href="{{ $href }}" data-turbo="true" @endif
  {{
    $attributes->class([
      'flex w-full items-center gap-3 border-b border-hairline px-4 py-3.5 text-left font-sans no-underline',
      'transition-[background-color] duration-(--duration-fast) ease-out last:border-b-0',
      'cursor-pointer hover:bg-hover focus-ring-inset' => (bool) $href,
    ])
  }}
>
  @isset($leading)
    {{ $leading }}
  @endisset

  @if (! isset($leading) && $icon)
    <span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-hairline bg-card text-body">
      <x-icon :name="$icon" :size="18" :stroke-width="1.5" />
    </span>
  @endif

  <div class="min-w-0 flex-1">
    @if ($title)
      <p class="text-base font-semibold text-ink">{{ $title }}</p>
    @endif

    @if ($description)
      <p class="mt-px text-sm text-pretty text-muted">{{ $description }}</p>
    @endif

    {{ $slot }}
  </div>

  @php
    $hasEnd = isset($end) && $end->isNotEmpty();
  @endphp

  @if ($meta || $metaSub || $hasEnd)
    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      @if ($meta || $metaSub)
        <div class="text-right">
          @if ($meta)
            <b class="block text-sm font-semibold whitespace-nowrap text-ink">{{ $meta }}</b>
          @endif

          @if ($metaSub)
            <span class="block text-xs whitespace-nowrap text-muted">{{ $metaSub }}</span>
          @endif
        </div>
      @endif

      @if ($hasEnd)
        {{ $end }}
      @endif
    </div>
  @endif
</{{ $tag }}>
