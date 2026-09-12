{{-- The row of tabs a record is read in, and what can be done to the tab that is open. --}}
{{--
  @var \App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel $viewModel
  @var \App\Models\Template $template
  @var bool $isEditable
--}}
@php
  $pages = $viewModel->pages();
  $selected = $viewModel->selectedPage();
  $newPageErrors = $errors->getBag('newPage');

@endphp
@if ($pages !== [])
  <div x-data="sortableRows" data-sortable>
    <x-tabs>
      @foreach ($pages as $page)
        @php
          $attributesForDragging = $isEditable
            ? new \Illuminate\View\ComponentAttributeBag([
              'draggable' => 'true',
              '@dragstart' => "start(\$event, {$page['id']})",
              '@dragover.prevent' => "over(\$event, {$page['id']})",
              '@drop.prevent' => "drop(\$event, {$page['id']})",
              '@dragend' => 'stop()',
              ':class' => "draggingId === {$page['id']} && 'opacity-40'",
            ])
            : new \Illuminate\View\ComponentAttributeBag;
        @endphp

        <x-tabs.item
          :href="$page['url']"
          :selected="$page['isSelected']"
          :count="$page['fieldCount']"
          :count-label="trans_choice(':count field on this page|:count fields on this page', $page['fieldCount'])"
          data-row-id="{{ $page['id'] }}"
          x-target="builder"
          {{ $attributesForDragging }}
        >{{ $page['label'] }}</x-tabs.item>
      @endforeach

      @if ($isEditable)
        <x-modal.form
          :title="__('Add a page')"
          :description="__('A tab of its own on the record, for things you only need when you look for them.')"
          :confirm="__('Add the page')"
          :action="route('settings.templatePages.create', $template)"
          :open="$newPageErrors->has('label')"
          x-target="builder"
        >
          <x-slot:trigger>
            <x-tabs.item action icon="plus" @click="open = true">{{ __('Add a page') }}</x-tabs.item>
          </x-slot:trigger>

          <x-input
            id="label"
            name="label"
            :label="__('Name of the page')"
            :value="old('label')"
            :placeholder="__('Work')"
            :help="__('It is what the tab says, so keep it to a word or two.')"
            :error="$newPageErrors->get('label')"
            maxlength="255"
            required
          />
        </x-modal.form>
      @endif
    </x-tabs>

    @if ($isEditable && $selected !== null)
      <div x-data="{ renaming: false, reordering: false }" class="flex flex-col gap-3 pt-2.5">
        <div class="flex flex-wrap items-center gap-x-1 gap-y-1">
          <span class="mr-1 text-xs text-pretty text-muted">{{ __('This page') }}</span>

          <x-button.ghost size="small" type="button" @click="renaming = ! renaming; reordering = false">{{ __('Rename') }}</x-button.ghost>

          <x-button.ghost size="small" type="button" @click="reordering = ! reordering; renaming = false">{{ __('Reorder') }}</x-button.ghost>

          @if ($viewModel->canRemovePage())
            <x-modal.warning
              :title="__('Remove “:name”?', ['name' => $selected->label])"
              :confirm="__('Remove the page')"
              :action="route('settings.templatePages.destroy', [$template, $selected])"
              x-target="builder"
            >
              <x-slot:trigger>
                <x-button.ghost size="small" type="button" @click="open = true" class="hover:text-error">{{ __('Remove') }}</x-button.ghost>
              </x-slot:trigger>

              <p>{{ __('It holds nothing, so nothing is deleted with it. The remaining tabs keep their order.') }}</p>
            </x-modal.warning>
          @else
            <span
              aria-disabled="true"
              class="inline-flex h-8.5 cursor-default items-center rounded-md px-3.25 font-sans text-[13.5px] font-semibold text-placeholder opacity-50"
            >{{ __('Remove') }}</span>
          @endif
        </div>

        <x-form
          x-cloak
          x-show="renaming"
          method="put"
          :action="route('settings.templatePages.update', [$template, $selected])"
          x-target="builder"
          class="flex flex-wrap items-end gap-2"
        >
          <x-input
            :id="'page-'.$selected->id.'-label'"
            name="label"
            x-ref="rename"
            size="small"
            :value="$selected->label"
            :aria-label="__('Name of the page')"
            class="max-w-56"
            maxlength="255"
            required
          />

          <x-button size="small">{{ __('Save') }}</x-button>

          <x-button.ghost size="small" type="button" @click="renaming = false">{{ __('Cancel') }}</x-button.ghost>
        </x-form>

        <div x-cloak x-show="reordering" class="flex flex-wrap items-center gap-2 rounded-md border border-dashed border-hairline-strong bg-sunken px-3 py-2.5">
          @foreach ($pages as $index => $page)
            <span class="flex items-center gap-1 rounded-sm border border-hairline bg-canvas py-1 pr-1 pl-2.5 text-[13px] text-body">
              {{ $page['label'] }}

              @foreach ([['position' => $index, 'label' => __('Move :name left', ['name' => $page['label']]), 'flip' => true, 'shown' => $index > 0], ['position' => $index + 2, 'label' => __('Move :name right', ['name' => $page['label']]), 'flip' => false, 'shown' => $index < count($pages) - 1]] as $move)
                @if ($move['shown'])
                  <x-form
                    method="put"
                    :action="route('settings.templatePagePosition.update', [$template, $page['uuid']])"
                    x-target="builder"
                    class="flex"
                  >
                    <input type="hidden" name="position" value="{{ $move['position'] }}" />

                    <button type="submit" aria-label="{{ $move['label'] }}" class="flex size-6 cursor-pointer items-center justify-center rounded-xs text-muted transition-colors duration-[110ms] hover:bg-hover hover:text-ink">
                      <x-icon name="chevron-right" :size="14" :stroke-width="1.7" :class="$move['flip'] ? 'rotate-180' : ''" />
                    </button>
                  </x-form>
                @endif
              @endforeach
            </span>
          @endforeach

          <x-button.ghost size="small" type="button" @click="reordering = false">{{ __('Done') }}</x-button.ghost>
        </div>
      </div>
    @endif

    @if ($isEditable)
      @foreach ($pages as $page)
        <x-position-form
          :action="route('settings.templatePagePosition.update', [$template, $page['uuid']])"
          target="builder"
          :row-id="$page['id']"
        />
      @endforeach
    @endif
  </div>
@endif
