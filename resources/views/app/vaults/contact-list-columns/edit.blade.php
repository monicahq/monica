{{-- Which columns the list of people in a vault shows, what they are called and in what order. --}}
{{-- @var \App\ViewModels\Vaults\ContactListColumnsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $wordings = [
    'moved' => __('Moved to position :position of :total.'),
    'added' => __('Added the :heading column.'),
    'removed' => __('Removed the :heading column.'),
  ];
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People'), 'url' => $viewModel->cancelUrl()], ['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <x-slot:footer>
    <div x-data="{ dirty: false }" @contact-list-columns-changed.window="dirty = true" class="flex w-full flex-wrap items-center justify-between gap-3">
      <x-button.ghost type="button" @click="window.dispatchEvent(new CustomEvent('contact-list-columns-reset'))">{{ __('Reset to defaults') }}</x-button.ghost>

      <div class="flex flex-wrap items-center gap-2.5">
        <span x-show="dirty" x-cloak class="text-sm text-muted">{{ __('Unsaved changes') }}</span>

        <x-button.secondary :href="$viewModel->cancelUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button type="submit" form="contact-list-columns-form">{{ __('Save changes') }}</x-button>
      </div>
    </div>
  </x-slot:footer>

  <x-page-header
    :title="$viewModel->title()"
    :description="__('Choose which details appear in your list and what their columns are called. This changes your list without changing contact templates.')"
  />

  <form
    id="contact-list-columns-form"
    method="post"
    action="{{ $viewModel->formUrl() }}"
    x-data="contactListColumns(@js($viewModel->columns()), @js($viewModel->defaultColumns()), @js($wordings))"
    data-column-editor
  >
    @csrf
    @method('PUT')

    <x-page-section :title="__('Columns')" :description="__('Tick a column to show it, drag the handle or use the arrow keys to reorder, and type over a heading to rename it. Name stays visible and first.')">
      <x-error :messages="$errors->all()" />

      <p class="sr-only" aria-live="polite" x-text="announcement"></p>

      <div role="list" class="overflow-hidden rounded-lg border border-hairline bg-canvas">
        <div role="listitem" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5">
          <span class="flex size-7 flex-none items-center justify-center text-muted-soft">
            <x-icon name="lock" :size="15" :label="__('Always shown, always first.')" />
          </span>

          <input type="checkbox" checked disabled aria-label="{{ __('Show :heading', ['heading' => __('Name')]) }}" class="size-4 flex-none accent-primary" />

          <div class="flex min-w-0 flex-[1_1_360px] flex-wrap items-center gap-x-4 gap-y-1.5">
            <span class="w-50 text-sm font-medium text-ink">{{ __('Name') }}</span>

            <span class="flex min-w-0 flex-[1_1_200px] flex-col gap-px">
              <span class="text-sm text-body">{{ __('Data source:') }} <span class="font-medium text-ink">{{ __('Name') }}</span></span>
              <span class="text-xs text-pretty text-muted">{{ __('Always shown, always first.') }}</span>
            </span>
          </div>
        </div>

        <template x-for="(column, index) in columns" :key="column.id">
          <div
            role="listitem"
            :draggable="armed === index ? 'true' : 'false'"
            @dragstart="start($event, index)"
            @dragover.prevent="over = index"
            @drop.prevent="drop(index)"
            @dragend="stop()"
            :class="{ 'opacity-60': dragging === index, 'shadow-[inset_0_2px_0_var(--color-ink)]': over === index && dragging !== null && dragging !== index }"
            class="flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-hairline px-3 py-2.5"
          >
            <input type="hidden" :name="`columns[${index}][source]`" :value="column.source" />
            <input type="hidden" :name="`columns[${index}][section]`" :value="column.section ?? ''" :disabled="column.section === null" />
            <input type="hidden" :name="`columns[${index}][field_key]`" :value="column.fieldKey ?? ''" :disabled="column.fieldKey === null" />
            <input type="hidden" :name="`columns[${index}][is_visible]`" :value="column.isVisible ? 1 : 0" />

            <button
              type="button"
              data-grip
              @pointerdown="armed = index"
              @pointerup="armed = null"
              @keydown="moveByKey($event, index)"
              :aria-label="@js(__('Reorder :heading. Use the arrow keys to move it.')).replace(':heading', column.heading || column.defaultHeading)"
              class="flex size-7 flex-none cursor-grab items-center justify-center rounded-md text-muted hover:bg-hover focus-ring"
            >
              <x-icon name="grip" :size="16" />
            </button>

            <input
              type="checkbox"
              x-model="column.isVisible"
              @change="changed()"
              :aria-label="@js(__('Show :heading')).replace(':heading', column.heading || column.defaultHeading)"
              class="size-4 flex-none cursor-pointer accent-primary"
            />

            <div class="flex min-w-0 flex-[1_1_360px] flex-wrap items-center gap-x-4 gap-y-1.5">
              <input
                type="text"
                maxlength="255"
                :name="`columns[${index}][heading]`"
                x-model="column.heading"
                @input="changed()"
                :placeholder="column.defaultHeading"
                :aria-label="@js(__('Column heading for :source')).replace(':source', column.sourceLabel)"
                class="h-8 min-w-35 flex-[0_1_200px] rounded-md border border-hairline bg-canvas px-2.5 text-sm font-medium text-ink placeholder:text-muted focus-ring"
              />

              <span class="flex min-w-0 flex-[1_1_200px] flex-col gap-px">
                <span class="text-sm text-body">{{ __('Data source:') }} <span class="font-medium text-ink" x-text="column.sourceLabel"></span></span>
                <span class="text-xs text-pretty" :class="column.isSupplied ? 'text-muted' : 'text-warning'" x-text="column.note"></span>
              </span>
            </div>

            <button
              type="button"
              @click="remove(index)"
              :aria-label="@js(__('Remove the :heading column')).replace(':heading', column.heading || column.defaultHeading)"
              class="flex size-7 flex-none cursor-pointer items-center justify-center rounded-md text-muted hover:bg-hover focus-ring"
            >
              <x-icon name="close" :size="15" />
            </button>
          </div>
        </template>
      </div>

      <x-actions-menu :label="__('Add a column')" :text="__('Add a column')" width="columns" align="left">
        <p class="border-b border-hairline px-2.5 pt-2 pb-1.5 text-xs text-pretty text-muted">{{ $viewModel->templatesLine() }}</p>

        @foreach ($viewModel->additions() as $group)
          <div x-show="! @js(array_column(array_column($group['items'], 'column'), 'id')).every((id) => has(id))" class="flex flex-col gap-0.5">
            <div class="flex flex-col gap-0.5 px-2.5 pt-2 pb-1">
              <x-caption>{{ $group['label'] }}</x-caption>
              <span class="text-xs text-pretty text-muted">{{ $group['help'] }}</span>
            </div>

            @foreach ($group['items'] as $item)
              <button
                type="button"
                role="menuitem"
                x-show="! has(@js($item['column']['id']))"
                @click="add(@js($item['column']))"
                class="flex w-full cursor-pointer flex-wrap items-center justify-between gap-x-2 gap-y-1 rounded-xs px-2.5 py-2 text-left font-sans text-sm transition-colors hover:bg-hover focus-ring-inset"
              >
                <span class="font-medium text-ink">{{ $item['label'] }}</span>

                @if ($item['templates'] !== [])
                  <span class="flex flex-wrap gap-1">
                    @foreach ($item['templates'] as $template)
                      <x-badge>{{ $template }}</x-badge>
                    @endforeach
                  </span>
                @endif
              </button>
            @endforeach
          </div>
        @endforeach
      </x-actions-menu>
    </x-page-section>
  </form>
</x-shell>
