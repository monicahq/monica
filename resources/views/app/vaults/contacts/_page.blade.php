{{--
  One page of the record: the sections the template puts on it, and the fields
  inside each.

  A field is a label and its answer, and nothing here has an answer yet: a value
  stored against a field is its own table and it has not been written. So what
  the page can honestly show is which questions it asks about somebody, each
  answered "not recorded" in the placeholder colour. That is the same treatment
  a field with nothing in it will keep once values arrive, so nothing about the
  page changes shape on the day they do.

  The type at the end of each row is what the field is, in mono, because it is a
  machine value rather than something about the person.

  It is its own file because it is the half of the screen the tabs swap, and the
  page that grows an ajax swap between tabs will aim at this.

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
  <div class="grid items-start gap-5 lg:grid-cols-2">
    @foreach ($sections as $section)
      <x-panel :title="$section['name']" :icon="$section['icon']" :icon-class="$section['iconClass']" :flush="true">
        @forelse ($section['fields'] as $field)
          <div class="flex items-baseline gap-4 border-b border-hairline px-4.5 py-2.75 last:border-b-0">
            <span class="w-37.5 flex-none text-xs tracking-caption text-muted uppercase">{{ $field['label'] }}</span>

            <span @class(['text-base text-pretty', $field['value'] ? 'text-ink' : 'text-placeholder'])>
              {{ $field['value'] ?? __('Not recorded') }}
            </span>

            <span class="ml-auto font-mono text-[11px] text-placeholder">{{ $field['typeLabel'] }}</span>
          </div>
        @empty
          <p class="px-4.5 py-2.75 text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
        @endforelse
      </x-panel>
    @endforeach
  </div>
@endif
