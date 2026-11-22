{{-- The administrators' support queue: the requests, the one open beside them, and who asked it. --}}
{{-- @var \App\ViewModels\Admin\SupportQueueViewModel $viewModel --}}
@php
  $queue = $viewModel->queue();
  $current = $viewModel->currentSummary();
  $context = $viewModel->context();
@endphp

<x-admin-layout :title="__('Support requests')">
  <header class="flex flex-col gap-1.5">
    <h1 class="text-page-title text-ink">{{ __('Support requests') }}</h1>
    <p class="max-w-prose text-base text-pretty text-body">{{ __('Requests people opened from the support area. Replies go to them by email and appear in their requests.') }}</p>
  </header>

  <x-tabs :aria-label="__('Views')">
    @foreach ($viewModel->views() as $view)
      <x-tabs.item :href="$view['url']" :selected="$view['selected']" :count="$view['count']">{{ $view['label'] }}</x-tabs.item>
    @endforeach
  </x-tabs>

  <form method="get" action="{{ route('admin.support.index') }}" class="flex flex-wrap items-end gap-2" role="search" aria-label="{{ __('Filter requests') }}">
    <input type="hidden" name="view" value="{{ $queue->view }}" />

    <div class="min-w-55 flex-[1_1_260px]">
      <x-input type="search" id="q" name="q" size="small" :label="__('Search')" :value="$queue->search" :placeholder="__('Subject, message, name, email or number')" />
    </div>

    <x-select id="category" name="category" size="small" :label="__('Category')">
      @foreach ($viewModel->categoryOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($queue->category === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="user" name="user" size="small" :label="__('User')">
      @foreach ($viewModel->userOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($queue->user === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="age" name="age" size="small" :label="__('Age')">
      @foreach ($viewModel->ageOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($queue->age === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-button.secondary size="small">{{ __('Filter') }}</x-button.secondary>

    @if ($queue->hasFilters())
      <x-button.ghost :href="$viewModel->clearFiltersUrl()" size="small">{{ __('Clear filters') }}</x-button.ghost>
    @endif
  </form>

  <div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
    <section aria-label="{{ __('Requests') }}" class="min-w-0">
      <div class="flex justify-between gap-2 px-1 pb-2 text-xs text-muted">
        <p>{{ $viewModel->countLine() }}</p>
        <p>{{ __('Oldest first') }}</p>
      </div>

      <x-list-panel
        :items="$viewModel->rows()"
        id="support-requests"
        :paginator="$viewModel->requests()"
        :size="$viewModel->size()"
        :default="$viewModel->defaultSize()"
      >
        <x-slot:empty>
          <x-empty-state :title="$viewModel->emptyTitle()">
            <x-slot:icon>
              <x-icon name="mail" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ $viewModel->emptyBody() }}
          </x-empty-state>
        </x-slot:empty>

        @foreach ($viewModel->rows() as $row)
          @include('app.admin.support._row', ['row' => $row])
        @endforeach
      </x-list-panel>
    </section>

    @if ($current !== null && $context !== null)
      <div class="grid min-w-0 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_16rem]">
        <section aria-labelledby="request-subject" class="min-w-0 space-y-4">
          <header class="space-y-2.5">
            <h2 id="request-subject" class="text-2xl leading-tight font-semibold tracking-tight text-ink text-pretty">{{ $current['subject'] }}</h2>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
              <x-badge :tone="$current['statusTone']" :icon="$current['statusIcon']">{{ $current['statusLabel'] }}</x-badge>
              <p class="text-sm text-body"><span class="font-mono text-ink">{{ $current['reference'] }}</span> · {{ $current['categoryLabel'] }} · {{ __('Opened :when', ['when' => $current['opened']]) }}</p>

              <x-form method="put" :action="route('admin.support.status.update', ['supportRequest' => $current['uuid']])" class="ml-auto flex items-end gap-2">
                <x-select id="status" name="status" size="small" :label="__('Move to')" :inline="true">
                  @foreach ($viewModel->statusOptions() as $option)
                    <option value="{{ $option['value'] }}" @selected($current['status'] === $option['value'])>{{ $option['label'] }}</option>
                  @endforeach
                </x-select>
                <x-button.secondary size="small">{{ __('Move') }}</x-button.secondary>
              </x-form>
            </div>
          </header>

          <div class="space-y-3">
            @foreach ($viewModel->thread() as $message)
              @include('app.admin.support._message', ['message' => $message])
            @endforeach
          </div>

          @include('app.admin.support._composer', ['current' => $current, 'statusOptions' => $viewModel->statusOptions()])
        </section>

        @include('app.admin.support._context', ['context' => $context, 'history' => $viewModel->history()])
      </div>
    @endif
  </div>
</x-admin-layout>
