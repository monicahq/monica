{{-- How often the dates about this contact were remembered over the last twelve months. --}}
{{-- @var \App\ViewModels\Vaults\ReminderAnswerSummary $summary --}}
@php
  $remembered = $summary->rememberedPercentage();
  $forgotten = $summary->forgottenPercentage();
@endphp
<div class="flex w-full flex-wrap items-center gap-x-7 gap-y-4 rounded-lg border border-hairline bg-canvas px-4 py-3 shadow-panel">
  <span
    role="img"
    aria-label="{{ $summary->chartLabel() }}"
    class="size-11 flex-none rounded-full"
    style="background: conic-gradient(var(--color-ink) 0 {{ $remembered }}%, var(--color-muted-soft) {{ $remembered }}% {{ $remembered + $forgotten }}%, var(--color-hairline) {{ $remembered + $forgotten }}% 100%)"
  ></span>

  <div class="flex flex-col gap-0.5">
    <span class="text-xl leading-tight font-semibold tracking-tight text-ink">{{ $remembered }}%</span>

    <span class="flex items-center gap-1.5 text-sm text-muted">
      <span aria-hidden="true" class="size-2 rounded-xs bg-ink"></span>
      {{ __('remembered') }}
    </span>
  </div>

  <div class="flex flex-col gap-0.5">
    <span class="text-xl leading-tight font-semibold tracking-tight text-ink">{{ $forgotten }}%</span>

    <span class="flex items-center gap-1.5 text-sm text-muted">
      <span aria-hidden="true" class="size-2 rounded-xs bg-muted-soft"></span>
      {{ __('forgot') }}
    </span>
  </div>

  <span class="text-sm text-muted">{{ $summary->totalLine() }}</span>
</div>
