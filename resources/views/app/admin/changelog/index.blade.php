{{-- The administrators' view of What’s new: comments to review, answer or hide, change by change. --}}
{{-- @var \App\ViewModels\Admin\ChangelogModerationViewModel $viewModel --}}
@php
  $rows = $viewModel->rows();
  $current = $viewModel->current();
  $filters = $viewModel->filters();
@endphp

<x-admin-layout :title="__('Changelog feedback')">
  <header class="flex flex-col gap-1.5">
    <h1 class="text-page-title text-ink">{{ __('Changelog feedback') }}</h1>
    <p class="max-w-prose text-base text-pretty text-body">{{ __('Comments and votes people left on What’s new. Public comments appear under the change; private ones only reach administrators.') }}</p>
  </header>

  @include('app.admin.changelog._tabs', ['tabs' => $viewModel->tabs()])

  <form method="get" action="{{ route('admin.changelog.index') }}" class="flex flex-wrap items-end gap-2" role="search" aria-label="{{ __('Filter comments') }}">
    <input type="hidden" name="tab" value="{{ $viewModel->tab() }}" />

    <div class="min-w-55 flex-[1_1_260px]">
      <x-input type="search" id="search" name="search" size="small" :label="__('Search')" :value="$filters['search']" :placeholder="__('Comment, change, name or email')" />
    </div>

    @foreach ([['type', __('Type'), $viewModel->typeOptions()], ['visibility', __('Visibility'), $viewModel->visibilityOptions()], ['period', __('Published'), $viewModel->periodOptions()]] as [$name, $label, $options])
      <x-select :id="$name" :name="$name" size="small" :label="$label">
        @foreach ($options as $option)
          <option value="{{ $option['value'] }}" @selected($filters[$name] === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
      </x-select>
    @endforeach

    <x-button.secondary size="small">{{ __('Filter') }}</x-button.secondary>
  </form>

  <x-error :messages="$errors->all()" />

  <div class="grid items-start gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
    <section aria-label="{{ __('Changes with comments') }}" class="min-w-0">
      <div class="flex justify-between gap-2 px-1 pb-2 text-xs text-muted">
        <p>{{ $viewModel->countLine() }}</p>
        <p>{{ __('Newest first') }}</p>
      </div>

      @if ($rows === [])
        <div class="rounded-lg border border-hairline bg-canvas">
          <x-empty-state :title="$viewModel->emptyTitle()">
            <x-slot:icon>
              <x-icon name="journal" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ $viewModel->emptyBody() }}
          </x-empty-state>
        </div>
      @else
        <ul class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
          @foreach ($rows as $row)
            <li @class(['border-t border-hairline' => ! $loop->first])>
              <a
                href="{{ $row['url'] }}"
                @if ($row['current']) aria-current="true" @endif
                @class([
                  'focus-ring-inset flex flex-col gap-0.75 px-4 py-3.5 no-underline hover:bg-hover',
                  'bg-hover shadow-[inset_2px_0_0_var(--color-ink)]' => $row['current'],
                ])
              >
                <span class="flex items-baseline gap-2">
                  <span class="min-w-0 flex-1 line-clamp-2 text-base font-semibold text-ink">{{ $row['title'] }}</span>
                  <span class="flex-none text-xs text-body">{{ $row['day'] }}</span>
                </span>
                <span class="text-xs text-body">{{ $row['meta'] }}</span>
                <span class="flex items-center gap-3 text-xs text-body">
                  <span>{{ trans_choice(':count like|:count likes', $row['up']) }}</span>
                  <span>{{ trans_choice(':count dislike|:count dislikes', $row['down']) }}</span>
                  <span class="ml-auto">{{ $row['matchLabel'] }}</span>
                </span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif
    </section>

    @if ($current !== null)
      <div class="grid min-w-0 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_15rem]">
        <section aria-labelledby="changelog-entry-title" class="flex min-w-0 flex-col gap-4">
          <header class="flex flex-col gap-2.5">
            <h2 id="changelog-entry-title" class="text-2xl font-semibold tracking-tight text-pretty text-ink">{{ $current['title'] }}</h2>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
              <x-badge :tone="$current['tone']">{{ $current['typeLabel'] }}</x-badge>
              <span class="text-sm text-body">{{ $current['meta'] }}</span>
            </div>
          </header>

          @foreach ($viewModel->comments() as $comment)
            @include('app.admin.changelog._comment', ['comment' => $comment])
          @endforeach
        </section>

        <aside class="flex min-w-0 flex-col gap-4">
          <section aria-labelledby="changelog-votes-title" class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
            <h3 id="changelog-votes-title" class="border-b border-hairline px-4 py-3 text-base font-semibold text-ink">{{ __('Votes') }}</h3>
            <div class="flex flex-col gap-3 p-4">
              <dl class="grid grid-cols-2 gap-3">
                <div class="flex flex-col-reverse gap-0.5">
                  <dt class="text-xs text-muted">{{ __('like it') }}</dt>
                  <dd class="text-xl font-semibold text-ink">{{ $current['up'] }}</dd>
                </div>
                <div class="flex flex-col-reverse gap-0.5">
                  <dt class="text-xs text-muted">{{ __('do not like it') }}</dt>
                  <dd class="text-xl font-semibold text-ink">{{ $current['down'] }}</dd>
                </div>
              </dl>
              <div class="flex h-1.5 overflow-hidden rounded-full bg-hairline" aria-hidden="true">
                @if ($current['up'] + $current['down'] > 0)
                  <span class="bg-success" style="width: {{ $current['upShare'] }}%"></span>
                  <span class="bg-error" style="width: {{ 100 - $current['upShare'] }}%"></span>
                @endif
              </div>
              <p class="text-sm text-body">{{ $current['ratioLine'] }}</p>
            </div>
          </section>

          <section aria-labelledby="changelog-file-title" class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
            <h3 id="changelog-file-title" class="border-b border-hairline px-4 py-3 text-base font-semibold text-ink">{{ __('Entry') }}</h3>
            <x-detail-list>
              <x-detail-list.row :label="__('ID')"><x-code-value>{{ $current['id'] }}</x-code-value></x-detail-list.row>
              <x-detail-list.row :label="__('File')"><x-code-value>{{ $current['file'] }}</x-code-value></x-detail-list.row>
              <x-detail-list.row :label="__('Comments')">{{ $current['commentSummary'] }}</x-detail-list.row>
            </x-detail-list>
            <div class="px-4 py-3">
              <a href="{{ $viewModel->whatsNewUrl() }}" class="focus-ring rounded-sm text-sm font-medium text-ink">{{ __('Open on What’s new') }}</a>
            </div>
          </section>
        </aside>
      </div>
    @endif
  </div>
</x-admin-layout>
