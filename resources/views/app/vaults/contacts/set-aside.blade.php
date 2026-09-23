{{-- The answers a record still holds for questions its structure no longer asks. --}}
{{-- @var \App\ViewModels\Vaults\SetAsideValuesViewModel $viewModel --}}
@php
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => route('contacts.index', $viewModel->vault())],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  @if ($values === [])
    <x-panel>
      <x-empty-state :title="__('No unused answers')">
        <x-slot:icon>
          <x-icon name="file" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ $viewModel->emptyLine() }}

        <x-slot:action>
          <x-button.secondary :href="$viewModel->contactUrl()" data-turbo="true" size="small">
            {{ __('Back to the record') }}
          </x-button.secondary>
        </x-slot:action>
      </x-empty-state>
    </x-panel>
  @else
    <x-panel :title="__('Answers to questions no longer asked')" icon="file" :flush="true">
      <dl class="grid gap-0">
        @foreach ($values as $value)
          <div class="grid grid-cols-2 items-baseline gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0 max-xs:grid-cols-1 max-xs:gap-0.5">
            <dt class="flex flex-col gap-0.5">
              <span class="text-sm font-semibold text-ink">{{ $value['label'] }}</span>

              <span class="font-mono text-2xs text-placeholder">{{ $value['key'] }}</span>
            </dt>

            <dd class="flex flex-col gap-0.5">
              <span @class(['text-base text-pretty', $value['value'] ? 'text-ink' : 'text-placeholder'])>
                {{ $value['value'] ?? __('Nothing was written') }}
              </span>

              <span class="text-xs text-muted">{{ __('Unused since :date', ['date' => $value['when']]) }}</span>
            </dd>
          </div>
        @endforeach
      </dl>
    </x-panel>

    <p class="px-0.5 text-sm text-pretty text-muted">
      {{ __('Put the field back on the template and these come back on their own.') }}

      <x-link :href="$viewModel->templateUrl()" data-turbo="true">{{ __('Open the template') }}</x-link>
    </p>
  @endif
</x-shell>
