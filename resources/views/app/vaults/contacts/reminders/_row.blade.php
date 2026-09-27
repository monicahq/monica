{{-- One reminder about a contact: what it says, when, how often, and what may be done about it. --}}
{{--
  @var \App\ViewModels\Vaults\ReminderRow $row
  @var bool $answered  true in the history, where a row shows its answer and offers nothing
--}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-3 border-t border-hairline px-4 py-3.5 first:border-t-0">
  <div class="flex min-w-60 flex-1 flex-col gap-1.5">
    <div class="flex flex-wrap items-center gap-2">
      <span class="font-semibold text-ink">{{ $row->text() }}</span>

      @if ($answered)
        <x-badge :tone="$row->tone()" :icon="$row->tone() === 'success' ? 'check' : null">{{ $row->outcome() }}</x-badge>
      @elseif ($row->isOverdue())
        <x-badge tone="danger">{{ __('Overdue') }}</x-badge>
      @endif
    </div>

    <div class="flex flex-wrap gap-x-4.5 gap-y-1 text-sm text-muted">
      <span class="flex items-center gap-1.5">
        <x-icon name="clock" :size="15" :stroke-width="1.5" />
        {{ $answered ? $row->answeredLine() : $row->whenLine() }}
      </span>

      <span class="flex items-center gap-1.5">
        <x-icon name="calendar-repeat" :size="15" :stroke-width="1.5" />
        {{ $row->cadence() }}
      </span>

      @if ($row->sourceLabel() !== null)
        <span class="flex items-center gap-1.5">
          <x-icon name="link" :size="15" :stroke-width="1.5" />
          {{ __('Follows') }}
          <a href="{{ $row->contactUrl() }}" data-turbo="true" class="text-body decoration-hairline-strong">{{ $row->sourceLabel() }}</a>
        </span>
      @endif
    </div>
  </div>

  @if (! $answered)
    <div class="flex items-center gap-2">
      @include('app.vaults.reminders._answers', ['row' => $row])

      @include('app.vaults.contacts.reminders._menu', ['row' => $row])
    </div>
  @endif
</div>
