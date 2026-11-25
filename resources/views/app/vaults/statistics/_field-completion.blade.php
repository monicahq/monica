{{-- Which fields of one template are filled in, field by field, for the records using it. --}}
{{-- @var \App\ViewModels\Vaults\RecordStatisticsViewModel $viewModel --}}
<x-page-section :title="__('Field completion')" id="field-completion">
  <form method="get" action="{{ $viewModel->completionUrl() }}" x-target="field-completion-results" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
    <x-select id="completion-template" name="template" :label="__('Template')" size="small" @change="$el.form.requestSubmit()">
      @foreach ($viewModel->completionTemplates() as $option)
        <option value="{{ $option['value'] }}" @selected($option['selected'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="completion-version" name="version" :label="__('Version')" size="small" @change="$el.form.requestSubmit()">
      @foreach ($viewModel->completionVersions() as $option)
        <option value="{{ $option['value'] }}" @selected($option['selected'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="completion-order" name="order" :label="__('Order')" size="small" @change="$el.form.requestSubmit()">
      @foreach ($viewModel->completionOrders() as $option)
        <option value="{{ $option['value'] }}" @selected($option['selected'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <noscript>
      <x-button type="submit" variant="secondary" size="small">{{ __('Show') }}</x-button>
    </noscript>
  </form>

  <div id="field-completion-results" x-merge="update" class="flex flex-col gap-4">
  <p role="status" class="sr-only">{{ $viewModel->completionStatus() }}</p>

  @if ($viewModel->completionIsEmpty())
    <p class="rounded-lg border border-hairline bg-card px-4 py-6 text-center text-sm text-muted">{{ __('No records use this template yet.') }}</p>
  @else
    <div class="flex flex-col gap-4">
      @foreach ($viewModel->completionSections() as $section)
        <div class="rounded-lg border border-hairline bg-card">
          <h3 class="border-b border-hairline px-4 py-2 text-xs font-medium text-muted uppercase">{{ $section['name'] }}</h3>

          <ul>
            @foreach ($section['fields'] as $field)
              <li class="flex flex-col gap-1.5 border-b border-hairline px-4 py-2.5 last:border-b-0">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                  <span class="text-sm font-medium text-ink">{{ $field['label'] }}</span>
                  <span class="text-sm text-body tabular-nums">{{ $field['figures'] }}</span>
                </div>

                <div class="flex items-center gap-3">
                  @if ($field['percentage'] !== null)
                    <x-meter :value="$field['percentage']" :label="$field['label']" class="max-w-60" />
                  @endif

                  <span class="shrink-0 text-xs text-muted">{{ $field['note'] }}</span>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  @endif
  </div>
</x-page-section>
