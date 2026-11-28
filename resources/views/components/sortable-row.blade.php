{{-- One row of a list somebody can reorder by dragging it, with the form that records where it landed. --}}
{{--
  @var int|string $rowId
  @var string $action
  @var string $target
  @var string $padding
  @var string|null $dragstart
--}}
@props([
  'rowId',
  'action',
  'target',
  'padding' => 'py-3.5 pe-4 ps-2',
  'dragstart' => null,
])

<div data-row-id="{{ $rowId }}" {{ $attributes->class(['relative border-b border-hairline last:border-b-0']) }}>
  <div
    x-cloak
    x-show="hoveringId === {{ $rowId }} && draggingId !== {{ $rowId }}"
    class="absolute inset-x-0 top-0 z-10 h-0.5 bg-primary"
    aria-hidden="true"
  ></div>

  <div
    draggable="true"
    @dragstart="{{ $dragstart ?? "start(\$event, {$rowId})" }}"
    @dragover.prevent="over($event, {{ $rowId }})"
    @drop.prevent="drop($event, {{ $rowId }})"
    @dragend="stop()"
    :class="draggingId === {{ $rowId }} && 'opacity-40'"
    class="flex w-full items-center gap-3 {{ $padding }} text-start transition-colors duration-(--duration-fast) ease-out hover:bg-hover"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder active:cursor-grabbing" aria-hidden="true">
      <x-icon name="grip" :size="15" :stroke-width="1.5" />
    </span>

    {{ $slot }}
  </div>

  <x-position-form :action="$action" :target="$target" :row-id="$rowId" />
</div>
