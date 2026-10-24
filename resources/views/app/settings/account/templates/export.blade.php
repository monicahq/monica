{{-- The file, in full, before it goes anywhere. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel $viewModel --}}
@php
  $template = $viewModel->template();
@endphp
<x-settings.page
  :title="__('Export :name', ['name' => $template->name])"
  :user="$viewModel->user()"
  current="templates"
  :crumbs="[
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => __('Share'), 'url' => $viewModel->shareUrl()],
      ['label' => __('Export a file')],
    ]"
  wide
>
  <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="flex min-w-0 flex-col gap-5">
      <x-page-header :title="__('Export :name', ['name' => $template->name])" :description="__('This is the file, in full. Read it, then keep it.')" class="max-w-prose" />

      <x-panel flush>
        <div class="flex items-center gap-3 border-b border-hairline bg-card px-4 py-3">
          <x-icon name="file" :size="16" :stroke-width="1.6" class="text-muted" />

          <code class="min-w-0 truncate font-mono text-xs text-ink">{{ $viewModel->fileName() }}</code>

          <span class="ml-auto shrink-0 text-xs text-muted">{{ $viewModel->lineLabel() }}</span>
        </div>

        <pre
          class="max-h-104 overflow-auto px-5 py-4 font-mono text-xs leading-relaxed text-ink-soft"
        >{{ $viewModel->json() }}</pre>
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

        <x-link :href="$viewModel->shareUrl()" turbo class="ml-auto text-sm">{{ __('Back to the two ways to share') }}</x-link>
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
            {{ __('The :records on this template, and everything written in their fields. The file describes the structure, never the answers.', ['records' => $viewModel->recordCountLabel()]) }}
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
</x-settings.page>
