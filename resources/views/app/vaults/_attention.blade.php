{{-- The dates of the vault that are overdue, due today or due within a week, each answerable here. --}}
{{-- @var \App\ViewModels\Vaults\VaultDashboardAttention $attention --}}
@php
  $bandTones = [
      'error' => 'bg-error-tint text-error',
      'warning' => 'bg-warning-tint text-warning',
      'default' => 'bg-card text-body',
  ];
@endphp
<x-page-section :title="__('Needs your attention')" :description="__('Overdue, today and the next seven days.')" icon="bell">
  <x-panel :flush="true">
    @if ($attention->isEmpty())
      <x-empty-state :title="__('Nothing due this week')">
        <x-slot:icon>
          <x-icon name="reminder" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Dates you asked to be reminded of appear here a week before they are due.') }}
      </x-empty-state>
    @else
      @foreach ($attention->bands() as $band)
        <section>
          <h3 class="flex items-center gap-2 border-b border-hairline px-4 py-2 text-xs font-semibold tracking-caption uppercase {{ $bandTones[$band['tone']] }}">
            <x-icon :name="$band['icon']" :size="14" :stroke-width="1.6" />

            {{ $band['title'] }}
          </h3>

          <ul>
            @foreach ($band['rows'] as $row)
              <li class="flex flex-wrap items-center gap-3 border-b border-hairline-soft px-4 py-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $bandTones[$band['tone']] }}">
                  <x-icon :name="$row->icon()" :size="20" :stroke-width="1.5" />
                </span>

                <span class="flex min-w-45 flex-1 flex-col">
                  @if ($row->contactName() === null)
                    <span class="text-base font-semibold text-muted">{{ __('A deleted contact') }}</span>
                  @else
                    <a
                      href="{{ $row->contactUrl() }}"
                      data-turbo="true"
                      class="self-start rounded-xs text-base font-semibold text-ink no-underline transition-colors duration-(--duration-fast) hover:text-link focus-ring"
                    >{{ $row->contactName() }}</a>
                  @endif

                  <span class="text-sm text-pretty text-body">{{ $row->text() }}</span>
                </span>

                <span @class(['text-sm font-medium whitespace-nowrap', 'text-error' => $band['isOverdue'], 'text-body' => ! $band['isOverdue']])>{{ $row->relative() }}</span>

                <span class="flex items-center gap-2">
                  @include('app.vaults.reminders._answers', ['row' => $row])
                </span>
              </li>
            @endforeach
          </ul>
        </section>
      @endforeach
    @endif

    <x-slot:footer>
      <x-link :href="$attention->remindersUrl()" :turbo="true">{{ __('All reminders') }}</x-link>
    </x-slot:footer>
  </x-panel>
</x-page-section>
