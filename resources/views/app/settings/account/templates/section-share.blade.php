{{--
  The same two ways out, scoped to one section.

  A section is the unit people actually reuse, because the rest of somebody's
  template is usually nobody else's business. That is the argument the screen
  makes, and it is why the list of what is going out sits at the top rather than
  behind a link: three field names is short enough to read, and reading them is
  how somebody notices they named a field after a person.

  @var \App\ViewModels\Settings\Account\Templates\ShareSectionViewModel $viewModel
--}}
@php
  $template = $viewModel->template();
@endphp
<x-shell :title="__('Share :name', ['name' => $viewModel->name()])" wide>
  <x-slot:bar>
    <x-breadcrumb :items="[
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => $viewModel->name()],
      ['label' => __('Share')],
    ]" />
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-6">
    <header class="flex max-w-[62ch] min-w-0 flex-col gap-2">
      <span class="flex items-center gap-2 text-xs font-semibold tracking-[0.06em] text-muted uppercase">
        <x-icon name="note" :size="15" :stroke-width="1.6" />
        {{ __('One section of :name', ['name' => $template->name]) }}
      </span>

      <h1 class="font-display text-page-title text-ink">{{ __('Share :name', ['name' => $viewModel->name()]) }}</h1>

      <p class="text-base text-pretty text-body">
        {{ __('The fields of this section and their choices, on their own. A section is the useful unit when the rest of your template is nobody else’s business: somebody can drop it into a template they already have.') }}
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
    <x-panel :title="__('Export the section as a file')" icon="file" icon-class="bg-sky-tint text-sky">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('The same format as a template file, one section deep. Importing it adds this section to the end of a template you choose, and never touches the sections already there.') }}
        </p>

        <div class="flex items-center gap-2.5 rounded-md border border-hairline bg-sand px-3.5 py-3">
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

    <x-panel :title="__('Publish the section')" icon="gift" icon-class="bg-accent-tint text-terracotta-deep">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('Sections are their own kind of listing in the marketplace, next to templates. They are the ones people install most, because they fit into whatever somebody already built.') }}
        </p>

        <x-notice tone="warning" :title="__('Writing a listing is not open yet')">
          {{ __('The catalogue is readable today, and publishing your own work into it is the next thing being built. Exporting a file works either way.') }}
        </x-notice>

        <div>
          <x-button.secondary :href="route('marketplace.index')">{{ __('Browse the marketplace') }}</x-button.secondary>
        </div>
      </div>
    </x-panel>
  </div>

  <x-notice tone="quiet" icon="lock" :title="__('What never leaves, either way')">
    {{ __('No contacts, no notes, no reminders, no files, no photos. A field called Nickname travels; the nicknames you wrote in it do not. You can read the whole file before it goes anywhere.') }}
  </x-notice>
</x-shell>
