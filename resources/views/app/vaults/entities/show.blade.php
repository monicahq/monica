{{-- One record of an entity type: its pages in a column, each section a panel of labelled values. --}}
{{-- @var \App\ViewModels\Vaults\EntityViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $pages = $viewModel->pages();
  $sections = $viewModel->sections();
  $colour = $viewModel->colour();
  $entry = 'focus-ring flex min-h-8 items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-sm no-underline transition duration-(--duration-fast) ease-out';
  $entryIdle = 'font-medium text-body hover:bg-hover hover:text-ink';
  $entryCurrent = 'bg-canvas font-semibold text-ink shadow-nav-active';
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => $viewModel->entityType()->name_plural, 'url' => $viewModel->listUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->current()" />
  </x-slot:sidebar>

  <div class="flex flex-col gap-7">
    <div class="flex flex-wrap items-center gap-4">
      <span
        class="flex size-14 flex-none items-center justify-center rounded-lg border border-hairline {{ $viewModel->tone() }}"
        style="background: color-mix(in srgb, {{ $colour }} 14%, var(--canvas))"
        aria-hidden="true"
      >
        <x-entity-icon :name="$viewModel->icon()" :size="28" />
      </span>

      <div class="flex min-w-0 flex-1 flex-col gap-1">
        <x-caption tone="inherit" class="{{ $viewModel->tone() }}">{{ $viewModel->typeName() }}</x-caption>
        <h1 class="text-page-title wrap-anywhere text-ink">{{ $viewModel->title() }}</h1>

        @if ($viewModel->headerFacts() !== [])
          <dl class="mt-1 flex flex-wrap gap-x-5.5 gap-y-2">
            @foreach ($viewModel->headerFacts() as $fact)
              <div class="flex items-center gap-1.75 text-sm text-body">
                <x-icon name="file" :size="16" :stroke-width="1.5" class="text-muted" />
                <dt>{{ $fact['label'] }}</dt>
                <dd class="font-semibold text-ink">{{ $fact['value'] }}</dd>
              </div>
            @endforeach
          </dl>
        @endif
      </div>

      @if ($viewModel->isArchived())
        <x-badge>{{ __('Archived') }}</x-badge>
      @endif

      <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true">{{ __('Edit') }}</x-button.secondary>
    </div>

    <div class="grid items-start gap-8 lg:grid-cols-[200px_minmax(0,1fr)]">
      <nav aria-label="{{ __('Pages') }}" class="flex flex-col gap-0.5 lg:sticky lg:top-4">
        <x-caption tone="muted" class="px-2.5 pb-2">{{ __('Pages') }}</x-caption>

        @foreach ($pages as $page)
          <a
            href="{{ $page['url'] }}"
            data-turbo="true"
            @if ($page['isSelected']) aria-current="page" @endif
            class="{{ $entry }} {{ $page['isSelected'] ? $entryCurrent : $entryIdle }}"
          >
            <span class="min-w-0 truncate">{{ $page['label'] }}</span>

            @if ($page['count'] > 0)
              <span class="text-muted" aria-hidden="true">{{ $page['count'] }}</span>
              <span class="sr-only">{{ trans_choice(':count field|:count fields', $page['count']) }}</span>
            @endif
          </a>
        @endforeach

        <div class="mx-2.5 mt-3 border-t border-hairline pt-3 text-xs text-muted">
          @if ($viewModel->templateUrl() !== null)
            <a href="{{ $viewModel->templateUrl() }}" class="text-muted underline decoration-hairline-strong underline-offset-3 hover:text-ink focus-ring">{{ $viewModel->templateLine() }}</a>
          @else
            {{ $viewModel->templateLine() }}
          @endif
        </div>
      </nav>

      <div class="flex min-w-0 flex-col gap-5">
        @forelse ($sections as $section)
          <x-panel :title="$section['name']" flush>
            @if ($section['fields'] === [])
              <p class="px-4.5 py-3 text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
            @else
              <x-detail-list label-width="180px">
                @foreach ($section['fields'] as $field)
                  <x-detail-list.row :label="$field['label']" value-class="">
                    <x-field-value
                      :field="$field['field']"
                      :value="$field['rawValue']"
                      :formatted="$field['value']"
                      :rows="$field['rows']"
                    />
                  </x-detail-list.row>
                @endforeach
              </x-detail-list>
            @endif
          </x-panel>
        @empty
          <x-panel>
            <x-empty-state :title="__('Nothing on this page yet')">
              <x-slot:icon>
                <x-icon name="file" :size="30" :stroke-width="1.4" />
              </x-slot:icon>

              {{ __('This page has no sections yet. What it asks is decided on the template.') }}

              @if ($viewModel->templateUrl() !== null)
                <x-slot:action>
                  <x-button.secondary :href="$viewModel->templateUrl()" data-turbo="true" size="small">
                    {{ __('Open the template') }}
                  </x-button.secondary>
                </x-slot:action>
              @endif
            </x-empty-state>
          </x-panel>
        @endforelse
      </div>
    </div>
  </div>
</x-shell>
