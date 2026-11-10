{{-- One date this vault's owner answered, with the answer they gave. --}}
{{-- @var \App\ViewModels\Vaults\ReminderRow $row --}}
<div class="flex w-full flex-wrap items-center gap-x-4 gap-y-2.5 border-b border-hairline px-4 py-3.5 last:border-b-0">
  <div class="flex min-w-0 flex-1 flex-col gap-1">
    <span class="flex min-w-0 flex-wrap items-center gap-2">
      <span class="truncate text-base font-semibold text-ink">{{ $row->text() }}</span>

      <x-badge :tone="$row->tone()" :icon="$row->tone() === 'success' ? 'check' : null">{{ $row->outcome() }}</x-badge>
    </span>

    <span class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
      <span>{{ $row->answeredLine() }}</span>

      @if ($row->recordName() !== null)
        <a href="{{ $row->recordUrl() }}" data-turbo="true" class="text-body no-underline hover:text-link">{{ $row->recordName() }}</a>
      @else
        <span>{{ __('A deleted contact') }}</span>
      @endif
    </span>
  </div>
</div>
