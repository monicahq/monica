{{-- A drawing of what a notification looks like once this channel carries it, said in words in the panel beside it. --}}
{{-- @var array $channel --}}
@php
  $on = $channel['is_enabled'];
  $title = __('Reminder: a birthday tomorrow');
  $body = __('The reminders you set arrive looking like this.');
@endphp

<div aria-hidden="true" class="min-w-0 rounded-lg border border-hairline bg-card p-3">
  <div class="flex items-center justify-between gap-2 px-0.5 pb-2.5">
    <x-caption>{{ __('Preview') }}</x-caption>

    <span class="text-[11px] text-muted-soft">{{ $on ? __('What you will see') : __('Switched off') }}</span>
  </div>

  <div class="relative flex h-42 flex-col overflow-hidden rounded-lg border border-hairline bg-canvas">
    @if ($channel['preview'] === 'window')
      <div class="flex h-6.5 shrink-0 items-center gap-1.5 border-b border-hairline bg-card px-2">
        <span class="size-1.5 rounded-full bg-muted-soft"></span>
        <span class="size-1.5 rounded-full bg-muted-soft"></span>
        <span class="size-1.5 rounded-full bg-muted-soft"></span>
      </div>

      <div class="flex flex-col gap-1.5 p-2.5">
        <span class="block h-1.75 w-[46%] rounded bg-hairline-strong"></span>
        <span class="block h-1.5 w-[78%] rounded bg-hairline"></span>
        <span class="block h-1.5 w-[64%] rounded bg-hairline"></span>
        <span class="block h-1.5 w-[71%] rounded bg-hairline"></span>
      </div>

      @if ($on)
        <div class="absolute inset-x-2.5 bottom-2.5 flex items-start gap-2.25 rounded-lg border border-hairline bg-canvas p-2.5 shadow-pop">
          <span class="flex size-5.5 shrink-0 items-center justify-center rounded-full bg-success-tint text-success">
            <x-icon name="reminder" :size="13" :stroke-width="1.5" />
          </span>

          <div class="min-w-0">
            <p class="text-[11px] font-semibold text-ink">{{ $title }}</p>
            <p class="mt-0.5 text-[11px] text-muted">{{ $body }}</p>
          </div>
        </div>
      @else
        <div class="absolute inset-x-2.5 bottom-2.5 flex h-13 items-center justify-center rounded-lg border border-dashed border-hairline-strong">
          <span class="text-[11px] text-muted">{{ __('Nothing arrives here') }}</span>
        </div>
      @endif
    @elseif ($channel['preview'] === 'inbox')
      <div class="flex h-6.5 shrink-0 items-center border-b border-hairline bg-card px-2.5">
        <span class="text-[11px] text-muted">{{ __('Inbox') }}</span>
      </div>

      @if ($on)
        <div class="flex gap-2.5 border-b border-hairline p-2.5">
          <span class="flex size-6 shrink-0 items-center justify-center rounded-full border border-hairline bg-card text-ink-soft">
            <x-icon name="reminder" :size="13" :stroke-width="1.5" />
          </span>

          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold text-ink">{{ config('app.name') }}</p>
            <p class="mt-0.5 text-[11px] font-medium text-ink-soft">{{ $title }}</p>
            <p class="mt-0.5 text-[11px] text-muted">{{ $body }}</p>
          </div>
        </div>
      @else
        @include('app.settings.account.notifications._preview-empty', ['icon' => $channel['icon']])
      @endif
    @else
      @if ($on)
        <div class="flex flex-1 flex-col justify-end gap-2 p-2.5">
          <div class="max-w-[86%] rounded-lg rounded-bl-xs border border-hairline bg-card p-2.5">
            <p class="text-[11px] font-semibold text-ink">{{ $title }}</p>
            <p class="mt-0.5 text-[11px] text-muted">{{ $body }}</p>
          </div>
        </div>
      @else
        @include('app.settings.account.notifications._preview-empty', ['icon' => $channel['icon']])
      @endif
    @endif
  </div>
</div>
