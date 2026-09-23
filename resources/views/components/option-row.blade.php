{{-- One option of a choice, drawn as a row that is itself the target. --}}
{{--
  @var string $id
  @var string $name
  @var string $value
  @var bool $checked
  @var string $type
  @var bool $chip
  @var string|null $hint
--}}
@props([
  'id',
  'name',
  'value' => '1',
  'checked' => false,
  'type' => 'checkbox',
  'chip' => false,
  'hint' => null,
])

@php
  $shell = $chip
      ? 'inline-flex h-8 items-center gap-2 rounded-md border px-3 py-0'
      : 'flex min-h-9.5 w-full items-center gap-2.75 rounded-md border px-3 py-2.5 text-left';
@endphp

<label
  for="{{ $id }}"
  @class([
    $shell,
    'cursor-pointer border-hairline bg-canvas text-base text-ink',
    'transition-colors duration-(--duration-fast) ease-out',
    'hover:bg-hover has-checked:border-hairline-strong has-checked:bg-hover',
    'has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45',
    'items-start' => $hint !== null,
  ])
>
  <input
    type="{{ $type }}"
    id="{{ $id }}"
    name="{{ $name }}"
    value="{{ $value }}"
    @checked($checked)
    {{ $attributes->class(['peer sr-only']) }}
  />

  <span
    aria-hidden="true"
    @class([
      'flex size-5 shrink-0 items-center justify-center border-[1.5px] border-hairline-strong bg-canvas text-transparent',
      'transition-colors duration-(--duration-fast) ease-out',
      'peer-checked:border-ink peer-checked:bg-ink peer-checked:text-page peer-checked:shadow-none',
      $type === 'radio' ? 'rounded-full' : 'rounded-sm',
      'mt-px' => $hint !== null,
    ])
  >
    @if ($type === 'radio')
      <span class="size-2 rounded-full bg-current"></span>
    @else
      <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
      </svg>
    @endif
  </span>

  @if ($hint === null)
    <span class="min-w-0">{{ $slot }}</span>
  @else
    <span class="flex min-w-0 flex-col gap-0.5">
      <span>{{ $slot }}</span>

      <span class="text-xs text-pretty text-muted">{{ $hint }}</span>
    </span>
  @endif
</label>
