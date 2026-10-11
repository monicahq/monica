{{-- One band of a screen: the name of what it holds, a line saying what it is for, and the thing itself. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var string|null $icon
  @var \Illuminate\View\ComponentSlot|null $aside
  @var \Illuminate\View\ComponentSlot|null $actions
--}}
@props([
  'title' => null,
  'description' => null,
  'icon' => null,
])

<section {{ $attributes->class(['flex flex-col gap-3']) }}>
  @if ($title)
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 @class(['shrink-0 text-section-title text-ink', 'flex items-baseline gap-2' => $icon])>
        @if ($icon)
          <x-icon :name="$icon" :size="17" :stroke-width="1.6" class="self-center text-muted" />
        @endif

        {{ $title }}
      </h2>

      @isset($description)
        <p class="text-sm text-pretty text-muted">{{ $description }}</p>
      @endisset

      @isset($aside)
        <span class="ml-auto text-xs text-pretty text-muted">{{ $aside }}</span>
      @endisset

      @isset($actions)
        <span class="ml-auto flex shrink-0 items-center gap-2 self-center">{{ $actions }}</span>
      @endisset
    </div>
  @endif

  {{ $slot }}
</section>
