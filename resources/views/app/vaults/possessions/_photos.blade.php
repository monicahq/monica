{{-- Where the photos of a possession will go once they can be added; nothing here can be used yet. --}}
<div class="flex flex-col gap-1.5">
  <p class="flex items-baseline gap-1.5 text-label text-ink">
    {{ __('Photos') }}
    <span class="text-xs text-muted">{{ __('optional') }}</span>
  </p>

  <div class="flex flex-wrap items-center gap-3">
    <div class="flex size-30 flex-col items-center justify-center gap-2 rounded-lg border-[1.5px] border-dashed border-hairline-strong bg-card text-muted" aria-hidden="true">
      <x-icon name="memory" :size="22" />
    </div>

    <p class="max-w-[34ch] text-xs text-muted">{{ __('Photos come in a later version.') }}</p>
  </div>
</div>
