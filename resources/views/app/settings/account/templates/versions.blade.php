{{-- Every version this template has had, and exactly what each one holds. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\TemplateVersionHistoryViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="templates"
  :back-href="$viewModel->backUrl()"
  :back-label="$viewModel->backLabel()"
>
  <x-page-header :title="$viewModel->title()" :description="__('Publishing fixes a template exactly as it is: its pages in their order, the version of each section, and every field. Records made with an older version keep it until you move them.')" class="min-w-0" />

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
</x-settings.page>
