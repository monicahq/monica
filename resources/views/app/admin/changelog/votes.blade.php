{{-- The votes people cast on the changes of What’s new over the last 30 days, change by change. --}}
{{-- @var \App\ViewModels\Admin\ChangelogVotesViewModel $viewModel --}}
@php
  $rows = $viewModel->rows();
@endphp

<x-admin-layout :title="__('Changelog feedback')">
  <header class="flex flex-col gap-1.5">
    <h1 class="text-page-title text-ink">{{ __('Changelog feedback') }}</h1>
    <p class="max-w-prose text-base text-pretty text-body">{{ __('Comments and votes people left on What’s new. Public comments appear under the change; private ones only reach administrators.') }}</p>
  </header>

  @include('app.admin.changelog._tabs', ['tabs' => $viewModel->tabs()])

  <section aria-label="{{ __('Votes by change') }}" class="flex flex-col gap-3">
    <form method="get" action="{{ route('admin.changelog.votes.index') }}" class="flex flex-wrap items-end justify-between gap-3">
      <p class="text-base text-body">{{ $viewModel->summary() }}</p>

      <div class="flex items-end gap-2">
        <x-select id="sort" name="sort" size="small" :label="__('Sort by')">
          @foreach ($viewModel->sortOptions() as $option)
            <option value="{{ $option['value'] }}" @selected($viewModel->sort() === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
        <x-button.secondary size="small">{{ __('Sort') }}</x-button.secondary>
      </div>
    </form>

    <div class="overflow-x-auto rounded-lg border border-hairline bg-canvas shadow-panel">
      <table class="w-full min-w-190 border-collapse text-base">
        <thead>
          <tr class="bg-card">
            <th scope="col" class="border-b border-hairline px-4 py-2.5 text-start text-xs font-medium whitespace-nowrap text-muted">{{ __('Change') }}</th>
            <th scope="col" class="border-b border-hairline px-3 py-2.5 text-start text-xs font-medium whitespace-nowrap text-muted">{{ __('Published') }}</th>
            <th scope="col" class="border-b border-hairline px-3 py-2.5 text-end text-xs font-medium whitespace-nowrap text-muted">{{ __('Like it') }}</th>
            <th scope="col" class="border-b border-hairline px-3 py-2.5 text-end text-xs font-medium whitespace-nowrap text-muted">{{ __('Dislike it') }}</th>
            <th scope="col" class="border-b border-hairline px-3 py-2.5 text-start text-xs font-medium whitespace-nowrap text-muted">{{ __('Split') }}</th>
            <th scope="col" class="border-b border-hairline px-4 py-2.5 text-end text-xs font-medium whitespace-nowrap text-muted">{{ __('Comments') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $row)
            <tr>
              <td class="border-b border-hairline px-4 py-3">
                <div class="flex flex-col gap-1">
                  <span class="font-semibold text-ink">{{ $row['title'] }}</span>
                  <span class="flex items-center gap-2 text-xs text-muted">
                    <x-badge :tone="$row['tone']">{{ $row['typeLabel'] }}</x-badge>
                    {{ $row['area'] }}
                  </span>
                </div>
              </td>
              <td class="border-b border-hairline px-3 py-3 whitespace-nowrap text-body">{{ $row['published'] }}</td>
              <td class="border-b border-hairline px-3 py-3 text-end text-ink tabular-nums">{{ $row['up'] }}</td>
              <td class="border-b border-hairline px-3 py-3 text-end text-ink tabular-nums">{{ $row['down'] }}</td>
              <td class="border-b border-hairline px-3 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="flex h-1.5 w-24 overflow-hidden rounded-full bg-hairline" aria-hidden="true">
                    @if ($row['up'] + $row['down'] > 0)
                      <span class="bg-success" style="width: {{ $row['upShare'] }}%"></span>
                      <span class="bg-error" style="width: {{ 100 - $row['upShare'] }}%"></span>
                    @endif
                  </div>
                  <span class="text-xs whitespace-nowrap text-body">{{ $row['shareLabel'] }}</span>
                </div>
              </td>
              <td class="border-b border-hairline px-4 py-3 text-end">
                @if ($row['commentCount'] > 0)
                  <a href="{{ $row['commentsUrl'] }}" class="focus-ring rounded-sm text-sm whitespace-nowrap text-ink">{{ $row['commentLabel'] }}</a>
                @else
                  <span class="text-sm text-muted">{{ __('None') }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-base text-muted">{{ __('Nothing was published in the last 30 days.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</x-admin-layout>
