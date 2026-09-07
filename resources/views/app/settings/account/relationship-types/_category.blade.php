{{-- One display group on the list, and the types filed under it. --}}
{{-- @var array $category --}}
@php
  $renameErrors = $errors->getBag('category'.$category['id']);
@endphp
<section x-data="{ renaming: @js($renameErrors->has('name')) }" class="flex flex-col gap-2.5">
  <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-0.5">
    <div x-show="! renaming" class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
      <h2 class="shrink-0 text-section-title text-ink">{{ $category['name'] }}</h2>

      @if ($category['isSystem'])
        <span class="text-xs text-muted">{{ __('Supplied with your account') }}</span>
      @else
        <button
          type="button"
          @click="renaming = true; $nextTick(() => $refs.name.focus())"
          class="cursor-pointer text-xs text-muted underline underline-offset-2 transition-colors hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >{{ __('Rename') }}</button>

        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $category['name']])"
          :confirm="__('Delete the category')"
          :action="route('settings.relationshipCategories.destroy', $category['id'])"
          x-target="relationship-types"
        >
          <x-slot:trigger>
            <button
              type="button"
              @click="open = true"
              class="cursor-pointer text-xs text-muted underline underline-offset-2 transition-colors hover:text-error focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
            >{{ __('Delete') }}</button>
          </x-slot:trigger>

          @if ($category['isEmpty'])
            <p>{{ __('The category holds no relationship type, so nothing you have written will change.') }}</p>
          @else
            <p>{{ trans_choice('This category holds :count relationship type. Move it to another category first, and this one can go.|This category holds :count relationship types. Move them to another category first, and this one can go.', count($category['types'])) }}</p>
          @endif
        </x-modal.warning>
      @endif
    </div>

    <x-form
      x-show="renaming"
      x-cloak
      method="put"
      :action="route('settings.relationshipCategories.update', $category['id'])"
      x-target="relationship-types"
      class="flex flex-wrap items-center gap-2"
    >
      <label for="category-name-{{ $category['id'] }}" class="sr-only">{{ __('Name of the category') }}</label>

      <input
        type="text"
        id="category-name-{{ $category['id'] }}"
        name="name"
        x-ref="name"
        value="{{ old('name', $category['name']) }}"
        maxlength="255"
        required
        @if ($renameErrors->has('name')) aria-invalid="true" aria-describedby="category-name-{{ $category['id'] }}-error" @endif
        class="h-8 max-w-[280px] rounded-md border border-hairline-strong bg-input px-3 font-sans text-sm text-ink focus:border-focus focus:ring-[3px] focus:ring-focus-ring/20 focus:outline-none aria-[invalid=true]:border-error"
      />

      <x-button type="submit" size="small">{{ __('Save') }}</x-button>

      <button
        type="button"
        @click="renaming = false"
        class="cursor-pointer text-xs text-muted underline underline-offset-2 transition-colors hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
      >{{ __('Cancel') }}</button>

      <x-error :id="'category-name-'.$category['id'].'-error'" :messages="$renameErrors->get('name')" />
    </x-form>

    <div class="ml-auto flex shrink-0 items-center gap-1.5">
      @unless ($category['isFirst'])
        <x-form
          method="put"
          :action="route('settings.relationshipCategoryPosition.update', $category['id'])"
          x-target="relationship-types"
        >
          <input type="hidden" name="position" value="{{ $category['position'] - 1 }}" />

          <x-icon-button
            type="submit"
            icon="chevron-up"
            :label="__('Move :name above :other', ['name' => $category['name'], 'other' => $category['previous']])"
          />
        </x-form>
      @endunless

      @unless ($category['isLast'])
        <x-form
          method="put"
          :action="route('settings.relationshipCategoryPosition.update', $category['id'])"
          x-target="relationship-types"
        >
          <input type="hidden" name="position" value="{{ $category['position'] + 1 }}" />

          <x-icon-button
            type="submit"
            icon="chevron-down"
            :label="__('Move :name below :other', ['name' => $category['name'], 'other' => $category['next']])"
          />
        </x-form>
      @endunless
    </div>
  </div>

  <x-panel :flush="true">
    <div x-data="sortableRows" data-sortable>
    @forelse ($category['types'] as $type)
      @include('app.settings.account.relationship-types._row', ['type' => $type])
    @empty
      <div class="flex flex-wrap items-center gap-x-6 gap-y-3 px-4 py-5">
        <p class="min-w-[200px] flex-1 text-sm text-pretty text-body">{{ __('Nothing in this category yet. Types you add here are listed under its name.') }}</p>

        <x-button.secondary :href="route('settings.relationshipTypes.new', ['category' => $category['id']])" data-turbo="true" class="shrink-0">
          {{ __('Add a type here') }}
        </x-button.secondary>
      </div>
    @endforelse
    </div>
  </x-panel>
</section>
