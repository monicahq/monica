{{-- A section that deletes what the screen is about: a red panel, what goes with it, and the button. --}}
{{--
  @var string $title
  @var string $icon
  @var \Illuminate\View\ComponentSlot $trigger
--}}
@props([
  'title',
  'icon' => 'close',
])

<x-panel tone="danger" :title="$title" :icon="$icon" {{ $attributes }}>
  <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
    @if ($slot->hasActualContent())
      <div class="flex min-w-60 flex-1 flex-col gap-2 text-sm text-pretty text-body">{{ $slot }}</div>
    @endif

    <div class="ms-auto shrink-0">{{ $trigger }}</div>
  </div>
</x-panel>
