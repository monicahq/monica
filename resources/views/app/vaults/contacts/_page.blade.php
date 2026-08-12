{{--
  One page of the record: the sections the template puts on it, and a control
  for each field inside them.

  The page is the form rather than a read-only list with an edit screen behind
  it. A page is the unit somebody fills in, and it is also the only thing that
  can refuse a required field: a value saved on its own never sees the question
  nobody has reached yet.

  A field whose type has no control yet says so in place rather than being left
  out, so nobody wonders where the question went.

  It is its own file because it is the half of the screen the tabs swap.

  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
@php
  $sections = $viewModel->sections();
@endphp
@if ($sections === [])
  <x-panel>
    <x-empty-state :title="__('Nothing on this page yet')" icon-class="bg-sand text-muted">
      <x-slot:icon>
        <x-icon name="file" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ $viewModel->emptyPageLine() }}

      <x-slot:action>
        <x-button.secondary :href="$viewModel->templateUrl()" data-turbo="true" size="small">
          {{ __('Open the template') }}
        </x-button.secondary>
      </x-slot:action>
    </x-empty-state>
  </x-panel>
@else
  <x-form
    method="put"
    :action="$viewModel->saveUrl()"
    class="flex flex-col gap-5"
  >
    <div class="grid items-start gap-5 lg:grid-cols-2">
      @foreach ($sections as $section)
        <x-panel :title="$section['name']" :icon="$section['icon']" :icon-class="$section['iconClass']">
          @forelse ($section['fields'] as $field)
            <div @class(['pb-4 last:pb-0'])>
              <x-field-input
                :field="$field['field']"
                :value="$field['rawValue']"
                :formatted="$field['value']"
                :error="$errors->get('fields.'.$field['id'])"
              />
            </div>
          @empty
            <p class="text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
          @endforelse
        </x-panel>
      @endforeach
    </div>

    {{--
      The bar that saves the page. It sticks to the bottom of the window so a
      long page does not have to be scrolled to the end before it can be saved,
      and it says which page it is saving, since the tabs mean somebody may be
      looking at one of several.
    --}}
    <div class="sticky bottom-4 flex flex-wrap items-center gap-3 rounded-lg border border-hairline bg-canvas/95 px-4 py-3 shadow-sm backdrop-blur">
      <p class="text-sm text-pretty text-muted">{{ $viewModel->saveLine() }}</p>

      <div class="ml-auto flex items-center gap-2">
        <x-button.secondary :href="$viewModel->pageUrl()" size="small">
          {{ __('Cancel') }}
        </x-button.secondary>

        <x-button type="submit" size="small">
          {{ __('Save this page') }}
        </x-button>
      </div>
    </div>
  </x-form>
@endif
