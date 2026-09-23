{{-- One reminder this vault has already sent. --}}
{{-- @var \App\ViewModels\Vaults\ReminderRow $row --}}
<div class="flex w-full flex-wrap items-start gap-x-4 gap-y-2.5 border-b border-hairline px-4 py-3.5 last:border-b-0">
  <div class="flex min-w-0 flex-1 items-center gap-2.25">
    @if ($row->contactName() !== null)
      <x-contact-avatar :name="$row->contactName()" :url="$row->avatarUrl()" :size="26" />

      <div class="flex min-w-0 flex-col">
        <a
          href="{{ $row->contactUrl() }}"
          data-turbo="true"
          class="truncate text-base font-semibold text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:text-link"
        >{{ $row->contactName() }}</a>

        <span class="flex min-w-0 items-center gap-1.5">
          <x-icon :name="$row->icon()" :size="13" :stroke-width="1.5" class="shrink-0 text-muted" />

          <span class="min-w-0 truncate text-xs text-body">{{ $row->text() }}</span>
        </span>
      </div>
    @else
      <div class="flex min-w-0 flex-col">
        <span class="text-base font-semibold text-muted">{{ __('A deleted contact') }}</span>

        <span class="flex min-w-0 items-center gap-1.5">
          <x-icon :name="$row->icon()" :size="13" :stroke-width="1.5" class="shrink-0 text-muted" />

          <span class="min-w-0 truncate text-xs text-body">{{ $row->text() }}</span>
        </span>
      </div>
    @endif
  </div>

  <div class="flex flex-none flex-wrap items-center gap-1.5">
    @foreach ($row->channels() as $channel)
      <x-badge tone="info">{{ $channel }}</x-badge>
    @endforeach
  </div>

  <x-badge :tone="$row->tone()" class="flex-none">{{ $row->outcome() }}</x-badge>

  <x-tooltip :text="$row->sentExact()" :focusable="true" class="flex-none">
    <time
      datetime="{{ $row->sentIso() }}"
      class="border-b border-dotted border-hairline-strong text-xs whitespace-nowrap text-muted"
    >{{ $row->sentRelative() }}</time>
  </x-tooltip>
</div>
