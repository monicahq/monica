{{-- What may be done to the category the panel is showing: rename it, move it, switch it off, remove it. --}}
{{-- @var array $category --}}
@php
  $renameErrors = $errors->getBag('category'.$category['id']);
  $item = 'block w-full cursor-pointer rounded-xs px-2.5 py-2 text-left font-sans text-sm transition-colors hover:bg-hover focus-ring-inset';
@endphp
<div x-data="{ renaming: @js($renameErrors->has('name')) }" class="flex shrink-0 items-center gap-2">
  <x-form
    x-show="renaming"
    x-cloak
    method="put"
    :action="route('settings.lifeEventCategories.update', $category['id'])"
    x-target="life-event-types"
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
      class="h-8 max-w-70 rounded-md border border-hairline-strong bg-input px-3 font-sans text-sm text-ink focus:border-focus focus:ring-[3px] focus:ring-focus-ring/20 focus:outline-none aria-[invalid=true]:border-error"
    />

    <x-button type="submit" size="small">{{ __('Save') }}</x-button>

    <button
      type="button"
      @click="renaming = false"
      class="cursor-pointer text-xs text-muted underline underline-offset-2 transition-colors hover:text-ink focus-ring"
    >{{ __('Cancel') }}</button>

    <x-error :id="'category-name-'.$category['id'].'-error'" :messages="$renameErrors->get('name')" />
  </x-form>

  <div x-show="! renaming" class="flex shrink-0 items-center gap-2">
    <x-button.secondary
      type="button"
      size="small"
      @click="renaming = true; $nextTick(() => $refs.name.focus())"
    >{{ __('Rename') }}</x-button.secondary>

    <x-actions-menu :label="__('More actions for :name', ['name' => $category['name']])" width="wide">
      <x-slot:glyph>
        <x-icon name="chevron-down" :size="14" :stroke-width="1.7" ::class="menuOpen && 'rotate-180'" class="transition-transform duration-[170ms]" />
      </x-slot:glyph>

      @unless ($category['isFirst'])
        <x-form
          method="put"
          :action="route('settings.lifeEventCategoryPosition.update', $category['id'])"
          x-target="life-event-types"
        >
          <input type="hidden" name="position" value="{{ $category['position'] - 1 }}" />

          <button type="submit" role="menuitem" class="{{ $item }} text-ink">
            {{ __('Move above :other', ['other' => $category['previous']]) }}
          </button>
        </x-form>
      @endunless

      @unless ($category['isLast'])
        <x-form
          method="put"
          :action="route('settings.lifeEventCategoryPosition.update', $category['id'])"
          x-target="life-event-types"
        >
          <input type="hidden" name="position" value="{{ $category['position'] + 1 }}" />

          <button type="submit" role="menuitem" class="{{ $item }} text-ink">
            {{ __('Move below :other', ['other' => $category['next']]) }}
          </button>
        </x-form>
      @endunless

      @if ($category['isActive'])
        <x-modal.form
          :title="__('Switch off “:name”?', ['name' => $category['name']])"
          :confirm="__('Switch the category off')"
          method="put"
          :action="route('settings.lifeEventCategoryActivation.update', $category['id'])"
          x-target="life-event-types"
        >
          <x-slot:trigger>
            <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="{{ $item }} text-ink">
              {{ __('Switch off') }}
            </button>
          </x-slot:trigger>

          <input type="hidden" name="is_active" value="0" />

          <p class="text-sm text-pretty text-body">{{ trans_choice('The :count type filed here leaves life event recording with the category.|The :count types filed here leave life event recording with the category.', $category['activeTypeCount']) }}</p>

          <p class="text-sm text-pretty text-body">{{ __('The life events you have already recorded keep their type and stay readable exactly as they are.') }}</p>
        </x-modal.form>
      @else
        <x-form
          method="put"
          :action="route('settings.lifeEventCategoryActivation.update', $category['id'])"
          x-target="life-event-types"
        >
          <input type="hidden" name="is_active" value="1" />

          <button type="submit" role="menuitem" class="{{ $item }} text-ink">{{ __('Switch back on') }}</button>
        </x-form>
      @endif

      @if (! $category['isSystem'] && $category['isEmpty'])
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $category['name']])"
          :confirm="__('Delete the category')"
          :action="route('settings.lifeEventCategories.destroy', $category['id'])"
          x-target="life-event-types"
        >
          <x-slot:trigger>
            <button type="button" role="menuitem" @click="open = true; menuOpen = false" class="{{ $item }} text-error">
              {{ __('Delete this category') }}
            </button>
          </x-slot:trigger>

          <p>{{ __('The category holds no type, so nothing you have written changes. This cannot be undone.') }}</p>
        </x-modal.warning>
      @else
        <p id="category-{{ $category['id'] }}-no-delete" class="px-2.5 py-1.5 text-xs text-pretty text-muted">
          {{ $category['isSystem']
              ? __('A category that came with your account cannot be deleted. Switch it off instead.')
              : __('A category can only be deleted once it holds no type.') }}
        </p>

        <button
          type="button"
          role="menuitem"
          aria-disabled="true"
          aria-describedby="category-{{ $category['id'] }}-no-delete"
          @click.prevent
          class="{{ $item }} cursor-not-allowed text-muted-soft hover:bg-transparent"
        >{{ __('Delete this category') }}</button>
      @endif
    </x-actions-menu>
  </div>
</div>
