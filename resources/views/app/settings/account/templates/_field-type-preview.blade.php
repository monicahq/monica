{{--
  One illustration of a filled field, drawn from the shape its type carries.

  Nothing here is a real control. It has no name, no input and no value that can
  be typed into, because it is showing somebody what a type produces before they
  own one: there is no record, no value list and no vault behind it. That is also
  why it is hidden from a screen reader as a whole and described in words
  instead, in the region the picker keeps for the purpose. A screen reader user
  read "a dropdown showing Dog" from the sentence; being walked through a fake
  combo box that answers nothing would be worse than useless.

  The nine kinds are the nine ways a value meets a form, and every type in the
  catalogue names one of them. A kind nothing answers to draws nothing rather
  than falling back, since a wrong illustration is worse than none.

  @var array<string, mixed> $preview
--}}
@php
  $kind = $preview['kind'];
  $value = $preview['value'] ?? '';
  $box = 'rounded-md border border-hairline-strong bg-input';
@endphp
<div aria-hidden="true">
  @if ($kind === 'input')
    <div class="{{ $box }} flex items-center overflow-hidden">
      @if (isset($preview['prefix']))
        <span class="border-r border-hairline-soft bg-sunken px-2.5 py-1.5 font-mono text-xs text-muted">{{ $preview['prefix'] }}</span>
      @endif

      <span class="min-w-0 truncate px-2.5 py-1.5 text-sm text-ink {{ ($preview['mono'] ?? false) ? 'font-mono' : '' }}">{{ $value }}</span>
    </div>
  @elseif ($kind === 'textarea')
    <div class="{{ $box }} grid gap-1.5 px-2.5 py-2.5">
      <span class="h-0.5 rounded-full bg-hairline-strong"></span>
      <span class="h-0.5 rounded-full bg-hairline-strong"></span>
      <span class="h-0.5 w-2/3 rounded-full bg-hairline-strong"></span>
    </div>
  @elseif ($kind === 'toggle')
    <div class="flex items-center gap-2">
      <span class="flex size-4 shrink-0 items-center justify-center rounded-xs bg-primary text-on-primary">
        <x-icon name="check" :size="11" :stroke-width="2" />
      </span>

      <span class="min-w-0 truncate text-sm text-ink">{{ $value }}</span>
    </div>
  @elseif ($kind === 'dropdown')
    <div class="{{ $box }} flex items-center gap-2 px-2.5 py-1.5">
      <span class="min-w-0 truncate text-sm text-ink">{{ $value }}</span>

      @if (isset($preview['caption']))
        <span class="shrink-0 font-mono text-xs text-muted-soft">{{ $preview['caption'] }}</span>
      @endif

      <x-icon name="chevron-down" :size="14" class="ml-auto text-muted" />
    </div>
  @elseif ($kind === 'list')
    <div class="grid gap-2">
      @foreach ($preview['options'] as [$text, $on])
        <div class="flex items-center gap-2">
          <span class="size-3.5 shrink-0 border {{ $preview['marker'] === 'dot' ? 'rounded-full' : 'rounded-xs' }} {{ $on ? 'border-primary bg-primary' : 'border-hairline-strong' }}"></span>

          <span class="text-sm text-ink">{{ $text }}</span>
        </div>
      @endforeach
    </div>
  @elseif ($kind === 'pills')
    <div class="flex flex-wrap gap-1.5">
      @foreach ($preview['options'] as [$text, $on])
        <span class="rounded-xs border px-2 py-0.5 text-xs {{ $on ? 'border-primary bg-sunken text-ink' : 'border-hairline-strong text-muted' }}">{{ $text }}</span>
      @endforeach
    </div>
  @elseif ($kind === 'upload')
    <div class="flex items-center gap-2.5 rounded-md border border-dashed border-hairline-strong bg-input px-3 py-2.5">
      <span class="size-6 shrink-0 border border-hairline-strong bg-sunken {{ ($preview['round'] ?? false) ? 'rounded-full' : 'rounded-xs' }}"></span>

      <span class="min-w-0 truncate text-xs text-muted">{{ $value }}</span>
    </div>
  @elseif ($kind === 'rows')
    <div class="grid gap-2">
      @foreach ($preview['rows'] as [$rowLabel, $width])
        <div class="grid gap-1 {{ ['full' => 'w-full', 'wide' => 'w-9/12', 'medium' => 'w-8/12', 'narrow' => 'w-6/12'][$width] }}">
          <span class="text-label text-muted">{{ $rowLabel }}</span>

          <span class="{{ $box }} block h-6.5"></span>
        </div>
      @endforeach

      @if ($preview['repeats'] ?? false)
        <span class="flex items-center gap-1.5 border-t border-hairline-soft pt-2 text-xs text-link">
          <x-icon name="plus" :size="13" />
          {{ __('Add another') }}
        </span>
      @endif
    </div>
  @elseif ($kind === 'layout')
    <div class="grid gap-2.5">
      <span class="{{ $box }} block h-6.5 w-8/12"></span>

      @if ($preview['layout'] === 'divider')
        <span class="block h-px bg-hairline-strong"></span>
      @elseif ($preview['layout'] === 'heading')
        <span class="text-card-title text-ink">{{ $value }}</span>
      @else
        <span class="text-xs text-pretty text-muted">{{ $value }}</span>
      @endif

      <span class="{{ $box }} block h-6.5 w-8/12"></span>
    </div>
  @endif
</div>
