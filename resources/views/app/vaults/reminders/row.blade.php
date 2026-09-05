{{-- One reminder on the list: when it fires, who it is about, and what it says. --}}
{{--
  @var array<string, mixed> $row
  @var bool $linkToReminder  false on the reminder's own screen, which is where this row is already
--}}
<div
  @class([
    'relative flex items-stretch rounded-lg border border-hairline bg-canvas shadow-panel transition-colors duration-(--duration-fast) ease-out hover:border-hairline-strong',
  ])
>
  <span aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden rounded-lg">
    <span class="absolute inset-x-0 top-0 h-6.5 bg-hairline/40 [clip-path:polygon(0_0,100%_0,50%_100%)]"></span>

    <span
      @class([
        'absolute inset-y-0 left-0 w-1',
        'bg-[repeating-linear-gradient(135deg,var(--color-warning)_0_6px,transparent_6px_12px)]' => $row['isLate'],
        'bg-[repeating-linear-gradient(135deg,var(--color-hairline-strong)_0_6px,transparent_6px_12px)]' => ! $row['isLate'],
      ])
    ></span>
  </span>

  <div class="relative flex flex-none flex-col items-center gap-1 py-2.75 pl-3.5">
    <span class="flex w-15.5 flex-col items-center rounded border-2 border-dashed border-hairline-strong px-0.75 py-1">
      <span class="text-[9px] font-semibold tracking-[0.1em] text-muted uppercase">{{ $row['month'] }}</span>
      <span class="text-[19px] leading-tight font-semibold text-ink">{{ $row['day'] }}</span>
      <span class="text-[9px] tracking-[0.06em] text-muted-soft">{{ $row['year'] }}</span>
    </span>

    @if ($row['cadence'] !== '')
      <span class="flex items-center gap-1">
        <x-icon :name="$row['cadenceIcon']" :size="11" :stroke-width="1.5" class="shrink-0 text-muted-soft" />
        <span class="text-[10px] text-muted">{{ $row['cadence'] }}</span>
      </span>
    @endif
  </div>

  <div class="relative flex min-w-0 flex-1 flex-wrap items-center gap-x-3.5 gap-y-2.5 py-3 pr-3.5 pl-4">
    <div class="flex min-w-0 flex-1 basis-full flex-col gap-1.5 sm:basis-0">
      <div class="flex min-w-0 items-center gap-2.25">
        @if ($row['contactName'] !== null)
          <x-contact-avatar :name="$row['contactName']" :url="$row['avatarUrl']" :size="26" />

          <a
            href="{{ $row['contactUrl'] }}"
            data-turbo="true"
            class="truncate text-[13.5px] font-semibold text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:text-link"
          >{{ $row['contactName'] }}</a>
        @else
          <span class="text-[13.5px] font-semibold text-muted">{{ __('Somebody who is gone') }}</span>
        @endif
      </div>

      <div class="flex min-w-0 items-start gap-2 border-t border-dashed border-hairline pt-1.5">
        <x-icon :name="$row['icon']" :size="15" :stroke-width="1.5" class="mt-px shrink-0 text-muted" />

        <span class="min-w-0 text-[13.5px] leading-snug text-pretty text-body">{{ $row['text'] }}</span>
      </div>
    </div>

    <x-tooltip :text="$row['exact']" :focusable="true" class="flex-none max-sm:ml-auto">
      <span
        @class([
          'border-b border-dotted border-hairline-strong text-xs whitespace-nowrap',
          'text-warning' => $row['isLate'],
          'text-muted' => ! $row['isLate'],
        ])
      >{{ $row['relative'] }}</span>
    </x-tooltip>

    <div class="flex flex-none items-center gap-1.5">
      @if (($linkToReminder ?? true) && $row['reminderUrl'] !== null)
        <x-icon-button
          :href="$row['reminderUrl']"
          data-turbo="true"
          icon="reminder"
          :label="__('Open this reminder')"
          align="right"
        />
      @endif

      @include('app.vaults.reminders._postpone', ['row' => $row])

      <x-form method="post" :action="$row['testUrl']" class="contents">
        <x-icon-button type="submit" icon="mail" :label="__('Send it to me now')" align="right" />
      </x-form>

      <x-form method="put" :action="$row['dismissUrl']" class="contents">
        <x-icon-button type="submit" icon="check" :label="__('I am done with this one')" align="right" />
      </x-form>
    </div>
  </div>
</div>
