{{-- One value list, and everything that can be done to it and to the values in it. --}}
{{-- @var array $list --}}
@php
  $listErrors = $errors->getBag('list'.$list['id']);
  $newValueErrors = $errors->getBag('newValue'.$list['id']);
  $target = 'value-list-'.$list['id'];
@endphp

<div
  id="{{ $target }}"
  x-merge="replace"
  x-data="{ renaming: {{ $listErrors->has('name') ? 'true' : 'false' }}, adding: {{ $newValueErrors->any() ? 'true' : 'false' }} }"
  class="dim-when-busy"
>
  <x-panel :title="$list['name']" :subtitle="$list['countLabel']" :flush="true">
    <x-slot:action>
      <div x-show="! renaming" class="flex items-center gap-0.5">
        @if ($list['holdsCountries'])
          <x-help
            :label="__('Help about the favourite countries')"
            :title="__('Why pick a country rather than type a name?')"
            guide="customise.value-lists#choose-your-favourite-countries"
            align="right"
          >{{ __('A country picked from the list is named in the language of whoever reads it, and its addresses are laid out the way that country writes them. A name you type is shown exactly as written, and its addresses keep the order you typed.') }}</x-help>
        @endif

        <x-button.ghost
          size="small"
          type="button"
          @click="renaming = true; $nextTick(() => $refs.name.focus())"
        >{{ __('Rename') }}</x-button.ghost>

        @if (! $list['canBeDeleted'])
          <x-button.ghost
            size="small"
            type="button"
            aria-disabled="true"
            :aria-describedby="'value-list-'.$list['id'].'-delete-reason'"
          >{{ __('Delete') }}</x-button.ghost>

          <span id="value-list-{{ $list['id'] }}-delete-reason" class="sr-only">{{ $list['deleteReason'] }}</span>
        @else
          <x-delete-action
            :title="__('Delete “:name”?', ['name' => $list['name']])"
            :confirm="__('Delete the list')"
            :action="route('settings.valueLists.destroy', $list['id'])"
            :label="__('Delete')"
            variant="ghost"
            size="small"
            x-target="value-lists"
          >
            <p>{{ __('The list is deleted, and every value in it with it.') }}</p>
          </x-delete-action>
        @endif
      </div>
    </x-slot:action>

    @if ($list['description'])
      <p class="px-4 py-3 text-sm text-pretty text-muted border-b border-hairline">{{ $list['description'] }}</p>
    @endif

    <x-form
      x-cloak
      x-show="renaming"
      method="put"
      :action="route('settings.valueLists.update', $list['id'])"
      :x-target="$target"
      class="flex flex-wrap items-start gap-3 border-b border-hairline px-4 py-3.5"
    >
      <x-input
        :id="'list-'.$list['id'].'-name'"
        name="name"
        x-ref="name"
        size="small"
        :value="$list['name']"
        :aria-label="__('Name of the list')"
        :error="$listErrors->get('name')"
        class="max-w-80"
        maxlength="255"
        required
      />

      <div class="flex items-center gap-2">
        <x-button size="small">{{ __('Save') }}</x-button>

        <x-button.ghost size="small" type="button" @click="renaming = false">{{ __('Cancel') }}</x-button.ghost>
      </div>
    </x-form>

    <div x-data="sortableRows" data-sortable>
      @forelse ($list['values'] as $value)
        @include('app.settings.account.value-lists._value', ['value' => $value, 'target' => $target])
      @empty
        <x-empty-state :title="__('No values yet')">
          <x-slot:icon>
            <x-icon name="tasks" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('Add the words you actually use. They are offered in the order you put them in.') }}
        </x-empty-state>
      @endforelse
    </div>

    @if (! $list['allowsUserValues'])
      <div class="border-t border-hairline px-3 py-2.5">
        <x-button.ghost size="small" type="button" aria-disabled="true" :aria-describedby="'value-list-'.$list['id'].'-add-reason'">
          <x-slot:icon>
            <x-icon name="plus" :size="15" :stroke-width="1.6" />
          </x-slot:icon>

          {{ __('Add a value') }}
        </x-button.ghost>

        <p id="value-list-{{ $list['id'] }}-add-reason" class="px-1 pt-1.5 text-xs text-pretty text-muted">{{ $list['addValueReason'] }}</p>
      </div>
    @else
      <div class="border-t border-hairline px-3 py-2.5">
        <x-button.ghost
          size="small"
          type="button"
          x-show="! adding"
          @click="adding = true; $nextTick(() => (document.getElementById('list-{{ $list['id'] }}-country-code') ?? $refs.label).focus())"
        >
          <x-slot:icon>
            <x-icon name="plus" :size="15" :stroke-width="1.6" />
          </x-slot:icon>

          {{ __('Add a value') }}
        </x-button.ghost>

        <x-form
          x-cloak
          x-show="adding"
          method="post"
          :action="route('settings.valueListItems.create', $list['id'])"
          :x-target="$target"
          class="flex flex-wrap items-start gap-3 px-1 py-1"
        >
          @if ($list['holdsCountries'])
            <x-country-picker
              :id="'list-'.$list['id'].'-country-code'"
              name="country_code"
              typed-name="label"
              :label="__('Country')"
              :placeholder="__('Choose a country')"
              :hint="__('Pick a country, or type a name for a place not in the list.')"
              :error="[...$newValueErrors->get('country_code'), ...$newValueErrors->get('label')]"
              class="w-full max-w-80"
            />
          @else
            <x-input
              :id="'list-'.$list['id'].'-label'"
              name="label"
              x-ref="label"
              size="small"
              :aria-label="__('Name of the value')"
              :placeholder="__('Name of the value')"
              :error="$newValueErrors->get('label')"
              class="max-w-80"
              maxlength="255"
              required
            />
          @endif

          <div class="flex items-center gap-2">
            <x-button size="small">{{ __('Add') }}</x-button>

            <x-button.ghost size="small" type="button" @click="adding = false">{{ __('Cancel') }}</x-button.ghost>
          </div>
        </x-form>
      </div>
    @endif
  </x-panel>
</div>
