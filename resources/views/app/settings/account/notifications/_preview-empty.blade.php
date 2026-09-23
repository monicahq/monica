{{-- What the preview shows for a channel that carries nothing. --}}
{{-- @var string $icon --}}
<div class="flex flex-1 flex-col items-center justify-center gap-2 p-4 text-center">
  <x-icon :name="$icon" :size="22" :stroke-width="1.4" class="text-muted-soft" />

  <span class="text-2xs text-muted">{{ __('Nothing arrives here') }}</span>
</div>
