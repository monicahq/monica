{{-- The page of one possession: where it is kept, its tags, and its Overview. --}}
{{-- @var \App\ViewModels\Vaults\PossessionViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $groups = $viewModel->groups();
@endphp

<x-shell :title="$viewModel->name()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $viewModel])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $viewModel, 'current' => 'overview'])

    <div class="grid min-w-0 grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_17rem]">
      <div class="flex min-w-0 flex-col gap-8">
        <div class="flex items-center gap-3 rounded-lg border border-dashed border-hairline-strong bg-card px-4 py-5 text-muted">
          <span class="flex" aria-hidden="true"><x-icon name="memory" :size="20" /></span>
          <p class="text-sm">{{ __('Photos come in a later version.') }}</p>
        </div>

        @if ($viewModel->description() !== null)
          <section aria-labelledby="possession-description-title" class="flex flex-col gap-2.5">
            <h2 id="possession-description-title" class="text-section-title text-ink">{{ __('Description') }}</h2>
            <p class="max-w-reading text-base leading-relaxed text-pretty whitespace-pre-line text-body">{{ $viewModel->description() }}</p>
          </section>
        @endif

        <section aria-labelledby="possession-details-title" class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
            <h2 id="possession-details-title" class="text-section-title text-ink">{{ __('Details') }}</h2>
            <p class="text-sm text-muted">{{ __('Fields from the :type type.', ['type' => $viewModel->typeName()]) }}</p>
          </div>

          <x-panel flush>
            @if ($groups === [])
              <p class="px-4 py-5 text-sm text-muted">{{ __('Nothing is filled in yet.') }}</p>
            @else
              @foreach ($groups as $group)
                <div>
                  @if ($group['title'] !== null)
                    <div class="border-b border-hairline bg-card px-4 py-2.25">
                      <x-caption>{{ $group['title'] }}</x-caption>
                    </div>
                  @endif

                  <dl class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))]">
                    @foreach ($group['fields'] as $field)
                      <div class="flex flex-col gap-0.75 border-e border-b border-e-hairline-soft border-b-hairline px-4 py-3">
                        <dt class="text-xs text-muted">{{ $field['label'] }}</dt>
                        <dd class="text-base text-ink">{{ $field['value'] }}</dd>
                      </div>
                    @endforeach
                  </dl>
                </div>
              @endforeach
            @endif
          </x-panel>
        </section>
      </div>

      <aside class="flex min-w-0 flex-col gap-4" aria-label="{{ __('About this possession') }}">
        <x-panel>
          <div class="flex flex-col gap-1.5">
            <span class="text-xs text-muted">{{ __('Current estimated value') }}</span>
            @if ($viewModel->value() !== null)
              <span class="text-[24px] font-semibold tracking-[-0.02em] text-ink tabular-nums">{{ $viewModel->value() }}</span>
            @else
              <span class="text-sm text-body">{{ __('No value recorded yet.') }}</span>
            @endif
            @if ($valuation = $viewModel->valuation())
              <span class="text-sm text-body">{{ $valuation['source'] }}</span>
              @if ($valuation['change'] !== null)
                <span class="text-sm text-muted">{{ $valuation['change'] }}</span>
              @endif
            @endif
          </div>
          <x-slot:footer>
            <x-link :href="$viewModel->recordValuationUrl()" class="text-sm">{{ __('Record a valuation') }}</x-link>
          </x-slot:footer>
        </x-panel>

        <x-panel flush>
          <dl class="divide-y divide-hairline-soft">
            @foreach ($viewModel->facts() as $fact)
              <div class="flex justify-between gap-4 px-4 py-2.75">
                <dt class="text-sm text-muted">{{ $fact['label'] }}</dt>
                <dd class="text-end text-sm font-medium text-ink">{{ $fact['value'] }}</dd>
              </div>
            @endforeach
          </dl>
        </x-panel>

        <p class="px-1 text-xs leading-relaxed text-muted">
          {{ $viewModel->addedLine() }}
          @if ($viewModel->editedLine() !== null)
            <br>{{ $viewModel->editedLine() }}
          @endif
        </p>
      </aside>
    </div>
  </div>
</x-shell>
