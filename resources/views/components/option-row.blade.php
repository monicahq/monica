{{--
  One option of a choice, drawn as a row that is itself the target.

  It replaces a hollow 16px ring with a label beside it. Three things were wrong
  with that: the target was the ring rather than the whole line, the selected
  option was told apart by colour alone, and a list of six of them was as tall as
  the block it sat in. Here the row is 42px and clickable end to end, selection
  fills both the mark and the row so it survives being printed in grey, and the
  caller can put the rows in two columns.

  The shape of the mark says how many may be picked before anybody tries: round
  for one of a list, square for as many as apply. That is the whole reason `type`
  is a prop and not an implementation detail.

  The mark is filled in ink, the same as the plain <x-checkbox>. There is one
  near black in this system and every mark in it is drawn in that, so a page of
  these reads as a page of marks rather than as a page of colours.

  The real input is hidden behind the drawn mark, exactly as <x-checkbox> does,
  so there is no javascript involved and the keyboard still works. A row of
  radios keyed by the same name is a group the arrow keys walk through, which is
  what the browser gives for free and what a <button> would have thrown away.

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
    'transition-[background-color,border-color] duration-(--duration-fast) ease-out',
    'hover:bg-hover has-[:checked]:border-hairline-strong has-[:checked]:bg-hover',
    'has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-focus-ring/45',
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
