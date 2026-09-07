{{-- Every version this template has had, and exactly what each one holds. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\TemplateVersionHistoryViewModel $viewModel --}}
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-button.bar :href="$viewModel->backUrl()" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ $viewModel->backLabel() }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <header class="flex min-w-0 flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <p class="max-w-[62ch] text-base text-pretty text-body">{{ __('Publishing fixes a template exactly as it is: its pages in their order, the version of each section, and every field. Records made with an older version keep it until you move them.') }}</p>
  </header>

  @if ($viewModel->hasVersions())
    @php
      $entries = $viewModel->entries();
      $last = count($entries) - 1;
    @endphp

    <ol class="flex flex-col">
      @foreach ($entries as $index => $entry)
        @include('app.settings.account.templates._version', [
          'entry' => $entry,
          'hasConnector' => $index < $last,
        ])
      @endforeach
    </ol>
  @else
    <x-panel>
      <x-empty-state :title="__('This template has no version yet')">
        {{ __('A version appears here as soon as the template has one to build on.') }}
      </x-empty-state>
    </x-panel>
  @endif
</x-shell>
