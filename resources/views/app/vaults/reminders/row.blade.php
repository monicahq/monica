{{-- One reminder on the list, drawn as an envelope: when it fires, who it is about, and what it says. --}}
{{--
  @var \App\ViewModels\Vaults\ReminderRow $row
  @var bool $linkToReminder  false on the reminder's own screen, which is where this row is already
--}}
@php
  $isLink = ($linkToReminder ?? true) && $row->reminderUrl() !== null;
  $item = 'flex w-full cursor-pointer items-center gap-2.5 rounded-xs px-2.5 py-2 text-start font-sans text-sm text-ink transition-colors hover:bg-hover focus-ring-inset';
@endphp
<div class="relative flex flex-wrap items-center gap-x-5 gap-y-3 rounded-lg border border-hairline bg-canvas px-5 pt-7 pb-4.5 shadow-panel transition-colors duration-(--duration-fast) ease-out hover:border-hairline-strong">
  <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-5.5 rounded-t-lg bg-card [clip-path:polygon(0_0,100%_0,50%_100%)]"></span>

  <{{ $isLink ? 'a' : 'div' }}
    @if ($isLink) href="{{ $row->reminderUrl() }}" data-turbo="true" @endif
    @class([
      'relative flex min-w-0 flex-[1_1_320px] items-center gap-5 text-body no-underline',
      'rounded-md focus-ring' => $isLink,
    ])
  >
    <span class="flex flex-none flex-col items-center gap-1">
      <span class="flex w-14 flex-col items-center rounded-xs border-[1.5px] border-dashed border-hairline-strong bg-canvas py-1.5">
        <span class="text-[10px] font-semibold tracking-widest text-muted uppercase">{{ $row->month() }}</span>
        <span class="text-[22px] leading-tight font-semibold text-ink">{{ $row->day() }}</span>
      </span>

      @if ($row->cadence() !== '')
        <span class="flex items-center gap-1 text-xs text-muted">
          <x-icon :name="$row->cadenceIcon()" :size="13" :stroke-width="1.5" class="shrink-0" />
          {{ $row->cadence() }}
        </span>
      @endif
    </span>

    <span class="flex min-w-0 flex-col gap-1.5">
      <span class="flex min-w-0 items-center gap-2">
        @if ($row->recordName() === null)
          <span class="text-base font-semibold text-muted">{{ __('A deleted contact') }}</span>
        @elseif ($isLink)
          <x-contact-avatar :name="$row->recordName()" :url="$row->avatarUrl()" :size="24" />

          <span class="truncate text-base font-semibold text-ink">{{ $row->recordName() }}</span>

          <x-icon name="chevron-right" :size="14" :stroke-width="1.6" class="shrink-0 text-muted" />
        @else
          <x-contact-avatar :name="$row->recordName()" :url="$row->avatarUrl()" :size="24" />

          <a
            href="{{ $row->recordUrl() }}"
            data-turbo="true"
            class="truncate text-base font-semibold text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:text-link"
          >{{ $row->recordName() }}</a>
        @endif
      </span>

      <span class="flex min-w-0 items-start gap-1.5 text-sm text-pretty text-body">
        <x-icon :name="$row->icon()" :size="15" :stroke-width="1.5" class="mt-0.5 shrink-0 text-muted" />

        <span class="min-w-0">
          {{ $row->text() }} ·
          <x-tooltip :text="$row->exact()" :focusable="! $isLink">
            <span
              @class([
                'border-b border-dotted border-hairline-strong whitespace-nowrap',
                'text-warning' => $row->isOverdue(),
              ])
            >{{ $row->relative() }}</span>
          </x-tooltip>
        </span>
      </span>
    </span>
  </{{ $isLink ? 'a' : 'div' }}>

  <div class="relative flex flex-none items-center gap-2 max-sm:ms-auto">
    @include('app.vaults.reminders._answers', ['row' => $row])

    <x-actions-menu variant="ghost" :text="__('More')" :label="__('More actions for :name', ['name' => $row->text()])">
      <x-slot:glyph>
        <x-icon name="chevron-down" :size="14" :stroke-width="1.7" ::class="menuOpen && 'rotate-180'" class="order-last transition-transform duration-(--duration-standard)" />
      </x-slot:glyph>

      @if ($row->testUrl() !== null)
        <button
          type="button"
          role="menuitem"
          data-test-url="{{ $row->testUrl() }}"
          @click="menuOpen = false; $refs.menuTrigger.focus(); $dispatch('confirm-reminder-test', { url: $el.dataset.testUrl })"
          class="{{ $item }}"
        >
          <x-icon name="mail" :size="16" :stroke-width="1.5" class="shrink-0" />

          {{ __('Send me a test') }}
        </button>
      @endif

      @if ($isLink)
        <div role="separator" class="my-1 h-px bg-hairline"></div>

        <x-actions-menu.item :href="$row->reminderUrl()" data-turbo="true">
          <x-slot:icon>
            <x-icon name="arrow-right" :size="16" :stroke-width="1.5" />
          </x-slot:icon>

          {{ __('Go to the reminder') }}
        </x-actions-menu.item>
      @endif
    </x-actions-menu>
  </div>
</div>
