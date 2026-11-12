{{-- A possession type written out as a file, to copy or to download. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeExportViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-types"
  :crumbs="$viewModel->breadcrumb()"
>
  <x-page-header
    :title="$viewModel->title()"
    :description="__('The file holds the name, the colour, the groups and the fields of this type, in order. It never holds a thing you own or a value you wrote.')"
  />

  <x-panel :title="$viewModel->fileName()" flush>
    <pre class="max-h-104 overflow-auto px-5 py-4 font-mono text-xs leading-relaxed text-ink-soft">{{ $viewModel->json() }}</pre>
  </x-panel>

  <div class="flex flex-wrap items-center gap-2.5">
    <x-button :href="$viewModel->downloadUrl()" download data-no-instant>{{ __('Download the file') }}</x-button>

    <x-copy-button
      variant="secondary"
      size="default"
      :text="$viewModel->json()"
      :label="__('Copy to the clipboard')"
      :failure="__('We could not copy it. Select the text and copy it by hand.')"
    />
  </div>

  <x-notice tone="quiet" icon="info">
    {{ __('Share it with somebody who wants the same type, or keep it before changing many fields. Importing it creates a new type and never changes this one.') }}
  </x-notice>
</x-settings.page>
