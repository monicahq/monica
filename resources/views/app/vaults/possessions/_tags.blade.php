{{-- The tags a new possession will carry, typed one by one, the account's own suggested as you type. --}}
{{-- @var \App\ViewModels\Vaults\PossessionFormViewModel $viewModel --}}
<x-page-section :title="__('Tags')" :description="__('Free words to find things across types, like “insured” or “for the kids”.')">
  <div x-data="tagInput({ chosen: @js($viewModel->chosenTags()), suggestions: @js($viewModel->accountTags()) })" class="flex flex-col gap-2">
    <label for="possession-tag-draft" class="sr-only">{{ __('Add a tag') }}</label>

    <div class="flex min-h-9 flex-wrap items-center gap-2 rounded-md border border-hairline-strong bg-input px-1.5 py-1 has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45">
      <template x-for="tag in tags" :key="tag">
        <span class="flex h-6.5 items-center gap-1 rounded-full bg-hover pr-1 pl-2.5 text-sm text-ink">
          <input type="hidden" name="tags[]" :value="tag" />
          <span x-text="tag"></span>
          <button
            type="button"
            @click="remove(tag)"
            :aria-label="@js(__('Remove the tag :name', ['name' => '__NAME__'])).replace('__NAME__', tag)"
            class="focus-ring flex size-5 items-center justify-center rounded-full text-muted hover:bg-hairline hover:text-ink"
          >
            <x-icon name="close" :size="12" :stroke-width="1.8" />
          </button>
        </span>
      </template>

      <input
        id="possession-tag-draft"
        type="text"
        x-ref="draft"
        x-model="draft"
        @keydown="keydown($event)"
        maxlength="50"
        autocomplete="off"
        aria-describedby="possession-tag-help"
        placeholder="{{ __('Add a tag, then press Enter') }}"
        class="h-6.5 min-w-45 flex-1 border-0 bg-transparent px-1.5 text-[16px] text-ink outline-none sm:text-sm"
      />
    </div>

    <p id="possession-tag-help" class="sr-only">{{ __('Press Enter or a comma to add the tag, and Backspace to remove the last one.') }}</p>

    <div x-show="matches.length > 0" x-cloak class="flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-muted">{{ __('Your tags') }}</span>
      <template x-for="match in matches" :key="match">
        <button type="button" @click="add(match)" class="focus-ring inline-flex h-6.5 items-center rounded-full border border-hairline bg-canvas px-2.5 text-xs text-ink hover:bg-hover" x-text="match"></button>
      </template>
    </div>

    <x-error id="tags-error" :messages="$errors->get('tags')" />
  </div>
</x-page-section>
