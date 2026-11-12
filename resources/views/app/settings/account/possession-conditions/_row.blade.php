{{-- One possession condition in the account's order, moved in place or turned into its own form. --}}
{{--
  @var array{uuid: string, name: string, colour: string, tint: string, colourName: string, icon: string|null, position: int, isFirst: bool, isLast: bool, updateAction: string, positionAction: string, destroyAction: string} $condition
  @var array<int, string> $glyphs
  @var list<array{value: string, label: string, tint: string, swatch: string}> $colours
--}}
@php
  $conditionErrors = $errors->getBag('possessionCondition'.$condition['uuid']);
  $moved = session('moved') === $condition['uuid'];
  $icon = $conditionErrors->any() ? old('icon', $condition['icon']) : $condition['icon'];
  $colour = $conditionErrors->any() ? old('colour', $condition['colour']) : $condition['colour'];
@endphp
<div
  role="listitem"
  x-data="{
    editing: @js($conditionErrors->any()),
    icon: @js((string) $icon),
    colour: @js($colour),
    close() {
      this.editing = false;
      this.icon = @js((string) $condition['icon']);
      this.colour = @js($condition['colour']);
      this.$nextTick(() => this.$refs.edit.focus());
    },
  }"
  @keydown.escape="if (editing) { close() }"
  class="border-b border-hairline last:border-b-0"
>
  {{-- Before the row in the markup, so the row is always the last child and its own edge never shows. --}}
  <x-form
    x-show="editing"
    x-cloak
    method="put"
    :action="$condition['updateAction']"
    x-target="possession-conditions"
    class="flex flex-col gap-3.5 bg-card p-4"
  >
    <div class="flex flex-wrap items-end gap-4">
      <x-mark-picker
        :id="'possession-condition-mark-'.$condition['uuid']"
        layout="split"
        :glyphs="$glyphs"
        :colours="$colours"
        :icon="$icon"
        :colour="$colour"
      />

      <div class="min-w-55 flex-1">
        <x-input
          :id="'possession-condition-name-'.$condition['uuid']"
          name="name"
          :label="__('Name')"
          :value="$conditionErrors->any() ? old('name', $condition['name']) : $condition['name']"
          :error="$conditionErrors->get('name')"
          maxlength="50"
          required
          x-ref="name"
        />
      </div>
    </div>

    @if ($conditionErrors->has('icon') || $conditionErrors->has('colour'))
      <x-error :messages="array_merge($conditionErrors->get('icon'), $conditionErrors->get('colour'))" />
    @endif

    <div class="flex justify-end gap-2">
      <x-button variant="secondary" size="small" type="button" @click="close()">{{ __('Cancel') }}</x-button>

      <x-button size="small" type="submit">{{ __('Save changes') }}</x-button>
    </div>
  </x-form>

  <x-sortable-row
    :row-id="$condition['position']"
    :action="$condition['positionAction']"
    target="possession-conditions"
    x-show="! editing"
  >
    <span class="flex size-8 flex-none items-center justify-center rounded-md" style="color: {{ $condition['colour'] }}; background: {{ $condition['tint'] }}" aria-hidden="true">
      @if ($condition['icon'])
        <x-icon :name="$condition['icon']" :size="16" :stroke-width="1.6" />
      @else
        <span class="size-2.5 rounded-full" style="background: {{ $condition['colour'] }}"></span>
      @endif
    </span>

    <span class="min-w-0 flex-1">
      <span class="block truncate font-semibold text-ink">{{ $condition['name'] }}</span>
      <span class="sr-only">{{ $condition['colourName'] }}</span>
    </span>

    <div class="flex flex-none items-center gap-1.5">
      @unless ($condition['isFirst'])
        <x-form method="put" :action="$condition['positionAction']" x-target="possession-conditions">
          <input type="hidden" name="position" value="{{ $condition['position'] - 1 }}" />
          <x-icon-button type="submit" icon="chevron-up" :label="__('Move :name up', ['name' => $condition['name']])" :x-autofocus="$moved && (session('direction') === 'up' || $condition['isLast'])" />
        </x-form>
      @endunless

      @unless ($condition['isLast'])
        <x-form method="put" :action="$condition['positionAction']" x-target="possession-conditions">
          <input type="hidden" name="position" value="{{ $condition['position'] + 1 }}" />
          <x-icon-button type="submit" icon="chevron-down" :label="__('Move :name down', ['name' => $condition['name']])" :x-autofocus="$moved && (session('direction') === 'down' || $condition['isFirst'])" />
        </x-form>
      @endunless

      <x-button
        variant="ghost"
        size="small"
        type="button"
        x-ref="edit"
        aria-label="{{ __('Edit :name', ['name' => $condition['name']]) }}"
        @click="editing = true; $nextTick(() => $refs.name.focus())"
      >{{ __('Edit') }}</x-button>

      <x-modal.warning
        :title="__('Delete the condition “:name”?', ['name' => $condition['name']])"
        :confirm="__('Delete condition')"
        :action="$condition['destroyAction']"
        x-target="possession-conditions"
      >
        <x-slot:trigger>
          <x-button
            variant="ghost"
            size="small"
            tone="danger"
            type="button"
            aria-label="{{ __('Delete :name', ['name' => $condition['name']]) }}"
            @click="open = true"
          >{{ __('Delete') }}</x-button>
        </x-slot:trigger>

        @if ($condition['deleteWarning'] !== '')
          <p>{{ $condition['deleteWarning'] }}</p>
        @endif
        <p>{{ __('This cannot be undone.') }}</p>
      </x-modal.warning>
    </div>
  </x-sortable-row>
</div>
