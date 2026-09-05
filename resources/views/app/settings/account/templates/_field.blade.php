{{-- One field on the list, and underneath it whatever a repeater asks for on every repetition. --}}
{{--
  @var array $field
  @var \App\Models\Template|null $template
  @var int|null $sectionId
  @var bool $isEditable
  @var bool $isChild
--}}
@php
  $isChild ??= false;
  $isEditable ??= false;
  $mayReorder = $isEditable && ! $isChild && isset($template, $sectionId);
@endphp

<div @if ($mayReorder) data-row-id="{{ $field['id'] }}" @endif class="relative">
  @if ($mayReorder)
    <div
      x-cloak
      x-show="hoveringId === {{ $field['id'] }} && draggingId !== {{ $field['id'] }}"
      class="absolute inset-x-0 -top-px z-10 h-0.5 bg-primary"
      aria-hidden="true"
    ></div>
  @endif

  <a
    href="{{ $field['url'] }}"
    x-target="builder"
    @if ($field['isSelected']) aria-current="true" @endif
    @if ($mayReorder)
      draggable="true"
      @dragstart="start($event, {{ $field['id'] }})"
      @dragover.prevent="over($event, {{ $field['id'] }})"
      @drop.prevent="drop($event, {{ $field['id'] }})"
      @dragend="stop()"
      :class="draggingId === {{ $field['id'] }} && 'opacity-40'"
    @endif
    @class([
      'grid w-full items-center gap-2.5 rounded-md border px-2.5 py-2.5 text-left transition-colors duration-[110ms] hover:bg-hover',
      'grid-cols-[auto_minmax(90px,1fr)_minmax(0,auto)]' => $mayReorder,
      'grid-cols-[minmax(90px,1fr)_minmax(0,auto)]' => ! $mayReorder,
      'border-hairline-strong bg-sunken' => $field['isSelected'],
      'border-transparent' => ! $field['isSelected'],
    ])
  >
    @if ($mayReorder)
      <span class="flex shrink-0 cursor-grab text-muted-soft active:cursor-grabbing" aria-hidden="true">
        <x-icon name="grip" :size="16" :stroke-width="1.4" />
      </span>
    @endif

    <span class="grid min-w-0 gap-px">
      <span class="truncate text-base font-semibold text-ink">{{ $field['label'] }}</span>

      <span class="truncate text-xs text-pretty text-muted">{{ $field['help'] ?? '—' }}</span>
    </span>

    <span class="flex max-w-48 flex-wrap items-center justify-end gap-1.5">
      @if ($field['isSensitive'])
        <x-badge tone="warning">{{ __('Sensitive') }}</x-badge>
      @endif

      @if ($field['repetitionLabel'] ?? null)
        <x-badge tone="outline">{{ $field['repetitionLabel'] }}</x-badge>
      @endif

      <x-badge tone="outline">{{ $field['typeLabel'] }}</x-badge>
    </span>
  </a>

  @if ($mayReorder)
    <x-form
      method="put"
      :action="route('settings.fieldPosition.update', [$template, $sectionId, $field['id']])"
      x-target="builder"
      :data-position-form="$field['id']"
      class="hidden"
    >
      <input type="hidden" name="position" value="" />
    </x-form>
  @endif

  @if ($field['children'] !== [])
    <div class="ml-3.5 flex flex-col gap-0.5 border-l border-hairline pl-2.5">
      @foreach ($field['children'] as $child)
        @include('app.settings.account.templates._field', [
          'field' => $child,
          'template' => $template ?? null,
          'sectionId' => $sectionId ?? null,
          'isEditable' => $isEditable,
          'isChild' => true,
        ])
      @endforeach
    </div>
  @endif
</div>
