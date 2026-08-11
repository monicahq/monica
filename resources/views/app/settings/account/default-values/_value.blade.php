{{--
  One value inside a list, either read or being renamed. Deleting it is asked
  about in a card in front of the screen rather than in the row, since a row is
  too small a place to say what is about to be lost.

  A value nothing uses is deleted outright; one that records already point at is
  retired instead, which is what "Stop offering" does. A retired value keeps its
  place in the list and says so in a badge as well as in the struck-out label, so
  it is never the styling alone that carries it.

  The row is draggable as a whole rather than by the handle alone, so the whole
  height of it is a target. The attribute is written out rather than bound, so
  the row is draggable from the first paint; the drag is instead refused while
  the field is open in it, since dragging a row somebody is typing in would
  throw the typing away.

  The hidden form at the foot is what the drag saves through. It is here rather
  than in the sortable component so that the csrf token, the method spoofing and
  the ajax target are written the way every other form on the screen writes them.

  @var array $value
  @var string $target
--}}
@php
  $valueErrors = $errors->getBag('value'.$value['id']);
@endphp

<div
  data-row-id="{{ $value['id'] }}"
  x-data="{ editing: {{ $valueErrors->has('label') ? 'true' : 'false' }} }"
  class="relative border-b border-hairline last:border-b-0"
>
  {{--
    The line showing where the dragged row would land. It is taken out of the
    flow, since a row that grows by two pixels under the pointer moves the row
    the pointer is on and the drag flickers between the two.
  --}}
  <div
    x-cloak
    x-show="hoveringId === {{ $value['id'] }} && draggingId !== {{ $value['id'] }}"
    class="absolute inset-x-0 top-0 z-10 h-0.5 bg-brand"
    aria-hidden="true"
  ></div>

  <div
    draggable="true"
    @dragstart="editing ? $event.preventDefault() : start($event, {{ $value['id'] }})"
    @dragover.prevent="over($event, {{ $value['id'] }})"
    @drop.prevent="drop($event, {{ $value['id'] }})"
    @dragend="stop()"
    :class="draggingId === {{ $value['id'] }} && 'opacity-40'"
    class="erica-row border-b-0 py-2 pl-2 transition-colors hover:bg-hover"
  >
    <span title="{{ __('Drag to reorder') }}" class="flex shrink-0 cursor-grab p-1 text-placeholder" aria-hidden="true">
      <x-icon name="menu" :size="15" :stroke-width="1.5" />
    </span>

    <div x-show="! editing" class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1">
      <p @class(['truncate', 'erica-row__title' => $value['isActive'], 'text-sm text-muted line-through' => ! $value['isActive']])>{{ $value['label'] }}</p>

      @unless ($value['isActive'])
        <x-badge>{{ __('no longer offered') }}</x-badge>
      @endunless

      <span class="erica-caption ml-auto">{{ $value['usageLabel'] }}</span>

      <div class="flex shrink-0 items-center gap-0.5">
        <x-button.ghost
          size="small"
          type="button"
          @click="editing = true; $nextTick(() => $refs.label.focus())"
        >{{ __('Edit') }}</x-button.ghost>

        @if ($value['isActive'] && $value['canBeDeleted'])
          <x-modal.warning
            :title="__('Delete “:value”?', ['value' => $value['label']])"
            :confirm="__('Delete the value')"
            :action="route('settings.valueListItems.destroy', $value['id'])"
            :x-target="$target"
          >
            <x-slot:trigger>
              <x-button.ghost size="small" type="button" @click="open = true" class="hover:text-error">{{ __('Delete') }}</x-button.ghost>
            </x-slot:trigger>

            <p>{{ __('Nothing uses it yet, so it goes for good and stops being offered anywhere.') }}</p>

            <p>{{ __('This cannot be undone.') }}</p>
          </x-modal.warning>
        @elseif ($value['isActive'])
          <x-form method="put" :action="route('settings.valueListItemActivation.update', $value['id'])" :x-target="$target">
            <input type="hidden" name="active" value="0" />

            <x-button.ghost size="small">{{ __('Stop offering') }}</x-button.ghost>
          </x-form>
        @else
          <x-form method="put" :action="route('settings.valueListItemActivation.update', $value['id'])" :x-target="$target">
            <input type="hidden" name="active" value="1" />

            <x-button.ghost size="small">{{ __('Offer again') }}</x-button.ghost>
          </x-form>
        @endif
      </div>
    </div>

    <div x-cloak x-show="editing" class="min-w-0 flex-1">
      <x-form method="put" :action="route('settings.valueListItems.update', $value['id'])" :x-target="$target" class="flex flex-wrap items-start gap-3">
        <x-input
          :id="'value-'.$value['id'].'-label'"
          name="label"
          x-ref="label"
          size="small"
          :value="$value['label']"
          :aria-label="__('Name of the value')"
          :error="$valueErrors->get('label')"
          class="max-w-80"
          maxlength="255"
          required
        />

        <div class="flex items-center gap-2">
          <x-button size="small">{{ __('Save') }}</x-button>

          <x-button.ghost size="small" type="button" @click="editing = false">{{ __('Cancel') }}</x-button.ghost>
        </div>
      </x-form>
    </div>
  </div>

  <x-form
    method="put"
    :action="route('settings.valueListItemPosition.update', $value['id'])"
    :x-target="$target"
    :data-position-form="$value['id']"
    class="hidden"
  >
    <input type="hidden" name="position" value="" />
  </x-form>
</div>
