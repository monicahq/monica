{{--
  The file, in full, before it goes anywhere.

  Showing it is the whole point of this screen. The promise on the share screen
  is that nothing anybody wrote into a record leaves, and the only honest way to
  make that promise is to put the bytes on the screen and let somebody read
  them. A download that cannot be read first asks to be trusted.

  What is shown and what is downloaded are the same text, formatting included,
  so reading it proves something.

  @var \App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel $viewModel
--}}
@php
  $template = $viewModel->template();
@endphp
<x-shell :title="__('Export :name', ['name' => $template->name])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => __('Share'), 'url' => $viewModel->shareUrl()],
      ['label' => __('Export a file')],
    ]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="flex min-w-0 flex-col gap-5" x-data="{ copied: false }">
      <header class="flex max-w-[66ch] flex-col gap-2">
        <h1 class="font-display text-page-title text-ink">{{ __('Export :name', ['name' => $template->name]) }}</h1>

        <p class="text-base text-pretty text-body">{{ __('This is the file, in full. Read it, then keep it.') }}</p>
      </header>

      <x-panel flush>
        <div class="flex items-center gap-3 border-b border-hairline bg-sand px-4 py-3">
          <x-icon name="file" :size="16" :stroke-width="1.6" class="text-muted" />

          <code class="min-w-0 truncate font-mono text-[12.5px] text-ink">{{ $viewModel->fileName() }}</code>

          <span class="ml-auto shrink-0 text-xs text-muted">{{ $viewModel->lineLabel() }}</span>
        </div>

        <pre
          x-ref="json"
          class="max-h-[26rem] overflow-auto px-5 py-4 font-mono text-[12.5px] leading-relaxed text-ink-soft"
        >{{ $viewModel->json() }}</pre>
      </x-panel>

      <div class="flex flex-wrap items-center gap-2.5">
        <x-button :href="$viewModel->downloadUrl()">{{ __('Download the file') }}</x-button>

        <x-button.secondary
          type="button"
          @click="navigator.clipboard.writeText($refs.json.textContent).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
        >
          <span x-show="! copied">{{ __('Copy to the clipboard') }}</span>

          <span x-cloak x-show="copied">{{ __('Copied') }}</span>
        </x-button.secondary>

        <x-link :href="$viewModel->shareUrl()" turbo class="ml-auto text-sm">{{ __('Back to the two ways') }}</x-link>
      </div>
    </div>

    <div class="flex flex-col gap-4">
      <x-panel :title="__('In the file')" flush>
        @foreach ($viewModel->inFile() as $row)
          <div class="flex items-baseline gap-3 border-b border-hairline px-4 py-2.5 last:border-b-0">
            <span class="w-24 shrink-0 text-xs text-muted">{{ $row['label'] }}</span>

            <span class="min-w-0 text-sm text-pretty text-ink">{{ $row['value'] }}</span>
          </div>
        @endforeach
      </x-panel>

      <x-panel>
        <div class="flex flex-col gap-2">
          <p class="text-base font-semibold text-ink">{{ __('Not in the file') }}</p>

          <p class="text-sm text-pretty text-ink-soft">
            {{ __('The :records on this template, and everything written in their fields. The file describes the form, never the answers.', ['records' => $viewModel->recordCountLabel()]) }}
          </p>
        </div>
      </x-panel>

      <x-panel variant="inset">
        <div class="flex flex-col gap-2">
          <p class="text-base font-semibold text-ink">{{ __('A field name can say a lot') }}</p>

          <p class="text-sm text-pretty text-ink-soft">
            {{ __('No values travel, but labels do. If you named a field after somebody, rename it before the file leaves.') }}
          </p>
        </div>
      </x-panel>
    </div>
  </div>
</x-shell>
