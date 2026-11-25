{{-- Step one of a change of template: which records leave which template, and where they go. --}}
{{-- @var \App\ViewModels\Vaults\TemplateChangeFormViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $destinations = $viewModel->destinations();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->sidebarCurrent()" />
  </x-slot:sidebar>

  <x-steps :steps="$viewModel->steps()" :current="1" />

  <x-page-header :title="$viewModel->title()" :description="$viewModel->context()" />

  <form method="get" action="{{ $viewModel->previewUrl() }}" class="flex max-w-reading flex-col gap-7">
    <input type="hidden" name="from" value="{{ $viewModel->sourceId() }}" />
    @if ($viewModel->versionNumber() !== null)
      <input type="hidden" name="version" value="{{ $viewModel->versionNumber() }}" />
    @endif

    <x-page-section :title="__('Records to move')">
      <div class="flex flex-col gap-3 rounded-lg border border-hairline bg-card px-4 py-3.5">
        <p class="flex flex-wrap items-baseline gap-x-2">
          <span class="text-xl font-semibold text-ink tabular-nums">{{ $viewModel->countLabel() }}</span>
          <span class="text-sm text-muted">{{ $viewModel->batchDescription() }}</span>
        </p>

        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Version') }}">
          @foreach ($viewModel->versionChoices() as $choice)
            <x-chip :href="$choice['url']" :selected="$choice['selected']" data-turbo="true">{{ $choice['label'] }}</x-chip>
          @endforeach
        </div>

        <x-link :href="$viewModel->viewRecordsUrl()" class="self-start text-sm focus-ring">{{ $viewModel->viewRecordsLabel() }}</x-link>
      </div>
    </x-page-section>

    <x-page-section :title="__('Move to')" :description="__('Other active, published templates for these records.')">
      <x-slot:help>
        <x-help
          :label="__('What happens to what is already written')"
          :title="__('What happens to what is already written')"
          guide="templates.change-many"
        >
          {{ __('Each value follows its field onto the new template. A value with no matching field is set aside, never deleted, and comes back if a later template has the field.') }}
        </x-help>
      </x-slot:help>

      @if ($destinations === [])
        <p id="template-change-refusal" class="text-sm text-muted">{{ __('No other template can take these records. Turn one on or publish one first.') }}</p>
      @elseif ($viewModel->isUpdate())
        <p class="rounded-lg border border-hairline bg-card px-4 py-3 text-sm text-ink">
          {{ __(':destination, current version', ['destination' => $destinations[0]['label']]) }}
        </p>
        <input type="hidden" name="to" value="{{ $destinations[0]['value'] }}" />
      @else
        <fieldset class="flex flex-col gap-2">
          <legend class="sr-only">{{ __('Move to') }}</legend>

          @foreach ($destinations as $destination)
            <x-option-row
              :id="'destination-'.$destination['value']"
              name="to"
              type="radio"
              :value="$destination['value']"
              :checked="$destination['checked']"
              :aria-describedby="$errors->has('to') ? 'to-error' : null"
              :aria-invalid="$errors->has('to') ? 'true' : null"
            >
              {{ $destination['label'] }}
              @if ($destination['isDefault'])
                <x-badge>{{ __('Default') }}</x-badge>
              @endif
            </x-option-row>
          @endforeach
        </fieldset>
      @endif

      <x-error id="to-error" :messages="$errors->get('to')" />
    </x-page-section>

    <div class="flex flex-wrap items-center justify-end gap-3">
      <x-button :href="$viewModel->backUrl()" type="button" variant="secondary" data-turbo="true">{{ __('Back to statistics') }}</x-button>
      @if ($destinations === [])
        <x-button type="button" aria-disabled="true" aria-describedby="template-change-refusal">{{ __('Preview changes') }}</x-button>
      @elseif ($viewModel->count() === 0)
        <p id="template-change-refusal" class="text-sm text-muted">{{ __('No record is left to move.') }}</p>
        <x-button type="button" aria-disabled="true" aria-describedby="template-change-refusal">{{ __('Preview changes') }}</x-button>
      @else
        <x-button type="submit">{{ __('Preview changes') }}</x-button>
      @endif
    </div>
  </form>
</x-shell>
