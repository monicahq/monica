{{--
  One value list, and everything that can be done to it: renamed, thrown away,
  added to, and its values renamed, reordered and retired.

  The whole panel is the target of every form inside it, so any of those swaps
  this block and leaves the other lists on the screen where they were. The swap
  replaces rather than morphs, because the flags that open a field in place are
  set in the browser and have to come back from the server closed: morphing
  would keep them, and the field would never shut.

  Each form is validated into a bag of its own, since every list on the screen
  has a field called `name` and every value one called `label`. Reading the bag
  is what reopens the one field that was rejected, and only that one.

  @var array $list
--}}
@php
  $listErrors = $errors->getBag('list'.$list['id']);
  $newValueErrors = $errors->getBag('newValue'.$list['id']);
  $target = 'value-list-'.$list['id'];
  $quiet = 'cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover';
  $quietButton = $quiet.' hover:text-ink';
  $dangerButton = $quiet.' hover:text-error';
@endphp

<div
  id="{{ $target }}"
  x-merge="replace"
  x-data="{ renaming: {{ $listErrors->has('name') ? 'true' : 'false' }}, adding: {{ $newValueErrors->has('label') ? 'true' : 'false' }} }"
  class="transition-opacity [&[aria-busy]]:opacity-60"
>
  <x-box padding="p-0">
    <x-slot:title>
      {{ $list['name'] }}

      <span class="ml-1.5 text-xs font-normal text-muted-soft">{{ $list['countLabel'] }}</span>
    </x-slot:title>

    <x-slot:actions>
      <div x-show="! renaming" class="flex items-center gap-0.5">
        <button type="button" @click="renaming = true; $nextTick(() => $refs.name.focus())" class="{{ $quietButton }}">{{ __('Rename') }}</button>

        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $list['name']])"
          :confirm="__('Delete the list')"
          :action="route('settings.valueLists.destroy', $list['id'])"
          x-target="value-lists"
        >
          <x-slot:trigger>
            <button type="button" @click="open = true" class="{{ $dangerButton }}">{{ __('Delete') }}</button>
          </x-slot:trigger>

          <p>{{ __('The list goes, and every value inside it goes with it.') }}</p>

          <p>{{ __('This cannot be undone.') }}</p>
        </x-modal.warning>
      </div>
    </x-slot:actions>

    <x-slot:description>
      @if ($list['description'])
        <p>{{ $list['description'] }}</p>
      @endif

      <x-form
        x-cloak
        x-show="renaming"
        method="put"
        :action="route('settings.valueLists.update', $list['id'])"
        :x-target="$target"
        class="flex flex-wrap items-start gap-3"
      >
        <x-input
          :id="'list-'.$list['id'].'-name'"
          name="name"
          x-ref="name"
          :value="$list['name']"
          :aria-label="__('Name of the list')"
          :error="$listErrors->get('name')"
          class="max-w-80"
          maxlength="255"
          required
        />

        <div class="flex items-center gap-2">
          <x-button>{{ __('Save') }}</x-button>

          <button type="button" @click="renaming = false" class="{{ $quietButton }}">{{ __('Cancel') }}</button>
        </div>
      </x-form>
    </x-slot:description>

    <div x-data="sortableRows" data-sortable>
      @forelse ($list['values'] as $value)
        @include('app.settings.account.default-values._value', ['value' => $value, 'target' => $target])
      @empty
        <x-empty-state :title="__('No values yet')">
          <x-slot:icon>
            <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
              <line x1="4" y1="4.4" x2="13" y2="4.4"></line>
              <line x1="4" y1="8" x2="13" y2="8"></line>
              <line x1="4" y1="11.6" x2="13" y2="11.6"></line>
            </svg>
          </x-slot:icon>

          {{ __('Add the words you actually use. They are offered in the order you put them in.') }}
        </x-empty-state>
      @endforelse
    </div>

    @if ($list['allowsUserValues'])
      <div class="border-t border-hairline-soft px-3 py-2">
        <button type="button" x-show="! adding" @click="adding = true; $nextTick(() => $refs.label.focus())" class="{{ $quietButton }} flex items-center gap-2">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
            <line x1="8" y1="3.2" x2="8" y2="12.8"></line>
            <line x1="3.2" y1="8" x2="12.8" y2="8"></line>
          </svg>
          {{ __('Add a value') }}
        </button>

        <x-form
          x-cloak
          x-show="adding"
          method="post"
          :action="route('settings.valueListItems.create', $list['id'])"
          :x-target="$target"
          class="flex flex-wrap items-start gap-3"
        >
          <x-input
            :id="'list-'.$list['id'].'-label'"
            name="label"
            x-ref="label"
            :aria-label="__('Name of the value')"
            :placeholder="__('Name of the value')"
            :error="$newValueErrors->get('label')"
            class="max-w-80"
            maxlength="255"
            required
          />

          <div class="flex items-center gap-2">
            <x-button>{{ __('Add') }}</x-button>

            <button type="button" @click="adding = false" class="{{ $quietButton }}">{{ __('Cancel') }}</button>
          </div>
        </x-form>
      </div>
    @endif
  </x-box>
</div>
