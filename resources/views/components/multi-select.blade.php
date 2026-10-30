{{-- A list of boxes to tick that unfolds under a button, the ticked ones shown beside it. --}}
{{--
  @var \Illuminate\Support\Collection<int, \App\Models\ValueListItem> $items
  @var string $id
  @var string $name
  @var array<int, int> $chosen
  @var string $label
  @var string|null $addLabel
  @var string|null $newName
--}}
@props([
  'items',
  'id',
  'name',
  'chosen' => [],
  'label',
  'addLabel' => null,
  'newName' => null,
])

@php
  $chip = 'inline-flex h-8 items-center gap-1 rounded-md border border-hairline-strong bg-hover pr-1 pl-3 text-base text-ink';
  $chipButton = 'focus-ring flex size-6 items-center justify-center rounded-sm text-muted hover:text-ink';
  $row = 'flex w-full cursor-pointer items-center gap-2.5 rounded-sm px-2.5 py-2 text-left text-base text-ink hover:bg-hover has-focus-visible:bg-hover';
@endphp

<div
  {{ $attributes->class(['flex flex-col gap-2 sm:flex-row sm:items-start']) }}
  x-data="{
    open: false,
    adding: false,
    wording: '',
    chosen: @js(array_map('strval', $chosen)),
    labels: @js($items->mapWithKeys(fn ($item) => [(string) $item->id => $item->label])->all()),
    added: [],
    announcement: '',
    same(one, other) {
      return one.toLowerCase() === other.toLowerCase();
    },
    close() {
      this.open = false;
      this.adding = false;
    },
    startAdding() {
      this.adding = true;
      this.$nextTick(() => this.$refs.wording.focus());
    },
    stopAdding() {
      this.adding = false;
      this.wording = '';
      this.$nextTick(() => this.$refs.add.focus());
    },
    add() {
      const wording = this.wording.trim();

      if (wording === '') {
        this.stopAdding();

        return;
      }

      const known = Object.keys(this.labels).find((id) => this.same(this.labels[id], wording));

      if (known !== undefined && ! this.chosen.includes(known)) {
        this.chosen.push(known);
      }

      if (known === undefined && ! this.added.some((other) => this.same(other, wording))) {
        this.added.push(wording);
      }

      this.announcement = @js(__('Added :label')).replace(':label', known === undefined ? wording : this.labels[known]);
      this.wording = '';
      this.$refs.wording.focus();
    },
  }"
>
  <div class="relative sm:w-48 sm:shrink-0" @click.outside="close()" @keydown.escape="close(); $refs.trigger.focus()">
    <x-button.secondary
      type="button"
      x-ref="trigger"
      @click="open = ! open"
      ::aria-expanded="open ? 'true' : 'false'"
      :aria-controls="$id.'-list'"
      class="w-full justify-start"
    >
      <span class="truncate">{{ __('Choose') }}</span>

      <x-icon
        name="chevron-down"
        :size="14"
        :stroke-width="1.7"
        class="ml-auto text-muted transition-transform duration-(--duration-standard)"
        ::class="open && 'rotate-180'"
      />
    </x-button.secondary>

    <div
      x-cloak
      x-show="open"
      id="{{ $id }}-list"
      role="group"
      aria-label="{{ $label }}"
      class="absolute top-[calc(100%+6px)] left-0 z-40 flex max-h-80 w-64 max-w-[calc(100vw-2rem)] flex-col overflow-y-auto rounded-lg border border-hairline bg-canvas p-1.5 shadow-pop"
    >
      @foreach ($items as $item)
        <label class="{{ $row }}">
          <input
            type="checkbox"
            name="{{ $name }}[]"
            value="{{ $item->id }}"
            x-model="chosen"
            @checked(in_array($item->id, $chosen))
            class="size-4 accent-ink"
          />
          <span class="min-w-0 flex-1 truncate">{{ $item->label }}</span>
        </label>
      @endforeach

      @if ($addLabel !== null)
        <template x-for="wording in added" :key="wording">
          <label class="{{ $row }}">
            <input
              type="checkbox"
              name="{{ $newName }}"
              :value="wording"
              checked
              @change="added = added.filter((other) => other !== wording)"
              class="size-4 accent-ink"
            />
            <span class="min-w-0 flex-1 truncate" x-text="wording"></span>
          </label>
        </template>

        <div class="mt-1 border-t border-hairline pt-1">
          <button
            type="button"
            x-ref="add"
            x-show="! adding"
            @click="startAdding()"
            class="{{ $row }} focus-ring-inset"
          >
            <x-icon name="plus" :size="15" :stroke-width="1.6" class="text-muted" />
            {{ $addLabel }}
          </button>

          <div x-cloak x-show="adding" class="flex items-center gap-1.5 p-1">
            <div class="min-w-0 flex-1">
              <x-input
                :id="$id.'-new'"
                :name="$newName"
                :aria-label="$addLabel"
                size="small"
                x-ref="wording"
                x-model="wording"
                @keydown.enter.prevent="add()"
                @keydown.escape.stop="stopAdding()"
              />
            </div>

            <x-icon-button :label="__('Add')" icon="enter" align="right" @click="add()" />
          </div>
        </div>
      @endif
    </div>
  </div>

  <ul class="flex flex-wrap gap-2 sm:pt-0.5">
    <template x-for="itemId in chosen" :key="itemId">
      <li class="{{ $chip }}">
        <span x-text="labels[itemId]"></span>
        <button
          type="button"
          class="{{ $chipButton }}"
          :aria-label="@js(__('Remove :name')).replace(':name', labels[itemId])"
          @click="chosen = chosen.filter((other) => other !== itemId); $refs.trigger.focus()"
        >
          <x-icon name="close" :size="14" :stroke-width="1.5" />
        </button>
      </li>
    </template>

    <template x-for="wording in added" :key="wording">
      <li class="{{ $chip }}">
        <span x-text="wording"></span>
        <button
          type="button"
          class="{{ $chipButton }}"
          :aria-label="@js(__('Remove :name')).replace(':name', wording)"
          @click="added = added.filter((other) => other !== wording); $refs.trigger.focus()"
        >
          <x-icon name="close" :size="14" :stroke-width="1.5" />
        </button>
      </li>
    </template>
  </ul>

  <p class="sr-only" aria-live="polite" x-text="announcement"></p>
</div>
