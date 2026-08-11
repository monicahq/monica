{{--
  The row of tabs a record is read in, and underneath it the handful of things
  that can be done to the tab which is open.

  A tab is a link rather than a button, so the page somebody is looking at has
  an address and the browser remembers where they were. It swaps the builder
  region rather than reloading, which is how the field links already work.

  Adding one asks in a card rather than in the strip itself. A form unfolding
  between the tabs pushes the whole row about, and naming a page is a decision
  of its own rather than something typed in passing.

  The order is changed by dragging a tab onto another, exactly as sections are
  reordered below: a hidden form per tab carries the place it landed in, and the
  drag handler submits the one belonging to the tab that moved. Dragging is not
  something a screen announces, and it is not something everybody can do, so
  "Reorder" opens the same move written out as buttons.

  Removing is offered only for a page that can actually go. A page holding
  sections, and the last page of a version, both stay, and the word is left in
  place saying why rather than becoming a button that comes back refused.

  @var \App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel $viewModel
  @var \App\Models\Template $template
  @var bool $isEditable
--}}
@php
  $pages = $viewModel->pages();
  $selected = $viewModel->selectedPage();
  $newPageErrors = $errors->getBag('newPage');

  /*
   * A rule runs the whole width and the tab you are on thickens its own piece of
   * it. No enclosing pill, no card-style active state, nothing that looks like a
   * button.
   */
  $tab = '-mb-px flex flex-none cursor-pointer items-center gap-2 border-b-2 border-transparent px-2.5 pt-2 pb-2.5 '
    .'text-sm text-muted no-underline transition-[color,border-color] duration-(--duration-fast) ease-out '
    .'hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring '
    .'aria-[selected=true]:border-b-ink aria-[selected=true]:font-semibold aria-[selected=true]:text-ink';
@endphp
@if ($pages !== [])
  <div x-data="sortableRows" data-sortable>
    <div role="tablist" class="flex flex-wrap items-end gap-1 border-b border-hairline">
      @foreach ($pages as $page)
        <a
          data-row-id="{{ $page['id'] }}"
          href="{{ $page['url'] }}"
          x-target="builder"
          role="tab"
          aria-selected="{{ $page['isSelected'] ? 'true' : 'false' }}"
          @if ($isEditable)
            draggable="true"
            @dragstart="start($event, {{ $page['id'] }})"
            @dragover.prevent="over($event, {{ $page['id'] }})"
            @drop.prevent="drop($event, {{ $page['id'] }})"
            @dragend="stop()"
            :class="draggingId === {{ $page['id'] }} && 'opacity-40'"
          @endif
          class="{{ $tab }}"
        >
          {{ $page['label'] }}

          <span
            @class([
              'flex h-5 min-w-5 items-center justify-center rounded-full border px-1.5 text-[11.5px] font-medium',
              $page['isSelected'] ? 'border-hairline-strong text-body' : 'border-hairline text-muted',
            ])
            title="{{ $page['fieldCount'] === 1 ? __('1 field on this page') : __(':count fields on this page', ['count' => $page['fieldCount']]) }}"
          >{{ $page['fieldCount'] }}</span>
        </a>
      @endforeach

      @if ($isEditable)
        <x-modal.form
          :title="__('Add a page')"
          :description="__('A tab of its own on the record, for the things that only matter when you go looking for them.')"
          :confirm="__('Add the page')"
          :action="route('settings.templatePages.create', $template)"
          :open="$newPageErrors->has('label')"
          x-target="builder"
        >
          <x-slot:trigger>
            <button type="button" @click="open = true" class="{{ $tab }}">
              <x-icon name="plus" :size="14" :stroke-width="1.6" />
              {{ __('Add a page') }}
            </button>
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
    </div>

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

              <p>{{ __('It holds nothing, so nothing goes with it. The tabs left keep their order.') }}</p>
            </x-modal.warning>
          @else
            {{--
              Left in place rather than taken away, so the row reads the same
              whichever tab is open. A page holding sections, and the last page
              of a version, both stay.
            --}}
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
        <x-form
          method="put"
          :action="route('settings.templatePagePosition.update', [$template, $page['uuid']])"
          x-target="builder"
          :data-position-form="$page['id']"
          class="hidden"
        >
          <input type="hidden" name="position" value="" />
        </x-form>
      @endforeach
    @endif
  </div>
@endif
