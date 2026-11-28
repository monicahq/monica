{{-- One group of things a life event is asked for, or the block holding the ones nobody grouped. --}}
{{--
  @var array<string, mixed> $block
  @var array<int, array{name: string, types: array<int, array{key: string, label: string}>}> $families
  @var array<string, string> $marks
  @var array<string, list<array<string, mixed>>> $settingsByType
  @var array<int, string> $choiceTypes
--}}
@php
  $reference = $block['reference'];
@endphp
<div
  data-block
  data-reference="{{ $reference }}"
  @unless ($block['isGroup']) data-loose @endunless
  @if ($block['isGroup'])
    draggable="true"
    @dragstart="startBlock($event, $el)"
    @dragover.prevent="overBlock($event, $el)"
    @drop.prevent="dropBlock($event, $el)"
    @dragend="stopDragging()"
  @endif
  class="overflow-hidden rounded-lg border border-hairline bg-canvas"
>
  <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-hairline bg-card px-3 py-2.5">
    @if ($block['isGroup'])
      <input type="hidden" name="groups[{{ $reference }}][placement_id]" value="{{ $block['placementId'] }}" />
      <input type="hidden" name="groups[{{ $reference }}][position]" value="0" data-block-position />

      <span title="{{ __('Drag to reorder') }}" aria-hidden="true" class="flex shrink-0 cursor-grab p-1 text-placeholder active:cursor-grabbing">
        <x-icon name="grip" :size="15" :stroke-width="1.5" />
      </span>

      <div class="min-w-40 flex-1 sm:max-w-80">
        <x-input
          :id="'group-'.$reference.'-name'"
          :name="'groups['.$reference.'][name]'"
          :value="$block['name']"
          :aria-label="__('Name of the block')"
          :placeholder="__('Name of the block')"
          size="small"
          maxlength="255"
          required
        />
      </div>

      <span class="shrink-0 text-xs text-muted">
        <span class="sr-only">{{ __('Fields in this block') }}</span>

        <span data-count>{{ count($block['fields']) }}</span>
      </span>

      <span class="ms-auto flex shrink-0 items-center gap-1">
        <x-icon-button type="button" icon="chevron-up" :label="__('Move this block up')" @click="moveBlock($el, -1)" />
        <x-icon-button type="button" icon="chevron-down" :label="__('Move this block down')" @click="moveBlock($el, 1)" />
        <x-icon-button type="button" icon="close" :label="__('Remove this block')" @click="removeBlock($el)" />
      </span>
    @else
      <span class="min-w-0 flex-1 text-card-title text-ink">{{ __('Not in a block') }}</span>

      <span class="shrink-0 text-xs text-muted">
        <span class="sr-only">{{ __('Fields in no block') }}</span>

        <span data-count>{{ count($block['fields']) }}</span>
      </span>
    @endif
  </div>

  <div data-fields @dragover.prevent="overFields($event)" @drop.prevent="dropFields($event, $el)">
    @foreach ($block['fields'] as $field)
      @include('app.settings.account.life-event-types._field', [
        'field' => $field,
        'families' => $families,
        'marks' => $marks,
        'settingsByType' => $settingsByType,
        'choiceTypes' => $choiceTypes,
      ])
    @endforeach
  </div>

  <p data-empty class="border-b border-hairline px-4 py-3.5 text-sm text-pretty text-muted @if ($block['fields'] !== []) hidden @endif">
    @if ($block['isGroup'])
      {{ __('Nothing in this block yet.') }}
    @else
      {{ __('A field added here is asked for on its own, above any block.') }}
    @endif
  </p>

  <div class="bg-card px-3 py-2.5">
    <x-button.secondary type="button" size="small" @click="addField($el)">
      <x-icon name="plus" :size="14" :stroke-width="1.6" />

      {{ $block['isGroup'] ? __('Add a field to this block') : __('Add a field') }}
    </x-button.secondary>
  </div>
</div>
