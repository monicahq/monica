{{-- The same two ways out, scoped to one section. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\ShareSectionViewModel $viewModel --}}
@php
  $template = $viewModel->template();
@endphp
<x-settings.page
  :title="__('Share :name', ['name' => $viewModel->name()])"
  :user="$viewModel->user()"
  current="templates"
  :crumbs="[
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => $viewModel->name()],
      ['label' => __('Share')],
    ]"
  wide
>
  <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-6">
    <header class="flex max-w-[62ch] min-w-0 flex-col gap-2">
      <x-caption class="flex items-center gap-2">
        <x-icon name="note" :size="15" :stroke-width="1.6" />
        {{ __('One section of :name', ['name' => $template->name]) }}
      </x-caption>

      <h1 class="text-page-title text-ink">{{ __('Share :name', ['name' => $viewModel->name()]) }}</h1>

      <p class="text-base text-pretty text-body">
        {{ __('The fields of this section and their choices, on their own. Share a section when the rest of your template is private. Somebody can add it to a template they already have.') }}
      </p>
    </header>

    <x-panel :title="__('What is going out')" flush class="w-full max-w-72 shrink-0">
      @foreach ($viewModel->fields() as $field)
        <div class="flex items-center gap-3 border-b border-hairline px-4 py-2.5 last:border-b-0">
          <span class="min-w-0 truncate text-sm text-ink">{{ $field['label'] }}</span>

          <span class="ml-auto shrink-0 text-xs text-muted">{{ $field['type'] }}</span>
        </div>
      @endforeach
    </x-panel>
  </div>

  <div class="grid items-start gap-6 lg:grid-cols-2">
    <x-panel :title="__('Export the section as a file')" icon="file">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('The same format as a template file, one section deep. Importing it adds this section to the end of a page you choose, and never touches the sections already there.') }}
        </p>

        <div class="flex items-center gap-2.5 rounded-md border border-hairline bg-card px-3.5 py-3">
          <x-icon name="file" :size="16" :stroke-width="1.6" class="text-muted" />

          <code class="min-w-0 truncate font-mono text-[12.5px] text-ink">{{ $viewModel->fileName() }}</code>

          <span class="ml-auto shrink-0 text-xs text-muted">{{ $viewModel->sizeLabel() }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-button :href="$viewModel->downloadUrl()">{{ __('Export the section') }}</x-button>

          <x-button.ghost :href="$viewModel->exportUrl()" data-turbo="true">{{ __('See what is inside') }}</x-button.ghost>
        </div>
      </div>
    </x-panel>

    <x-panel :title="__('Publish the section')" icon="gift">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('Sections are their own kind of listing in the marketplace, next to templates. They are installed most often, because they fit into a template somebody already built.') }}
        </p>

        <x-notice tone="warning" :title="__('Writing a listing is not open yet')">
          {{ __('You can read the catalogue today. Publishing your own work into it is the next thing being built. Exporting a file works either way.') }}
        </x-notice>

        <div>
          <x-button.secondary :href="route('marketplace.index')">{{ __('Browse the marketplace') }}</x-button.secondary>
        </div>
      </div>
    </x-panel>
  </div>

  <x-notice tone="quiet" icon="lock" :title="__('What never leaves, either way')">
    {{ __('No contacts, no notes, no reminders, no files, no photos. A field called Nickname is exported; the nicknames you wrote in it are not. You can read the whole file before it goes anywhere.') }}
  </x-notice>
</x-settings.page>
