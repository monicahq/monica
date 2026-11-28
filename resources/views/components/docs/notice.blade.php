{{-- A note in the documentation: the icon owns a full height column on the left, then a title and a message. --}}
{{-- @var string $title --}}
@props(['title'])

<div
  role="note"
  style="--accent: oklch(0.58 0.16 255)"
  {{ $attributes->class('flex overflow-hidden rounded-lg border border-[color-mix(in_oklch,var(--accent)_28%,var(--canvas))] bg-canvas') }}
>
  <span aria-hidden="true" class="flex w-13 shrink-0 items-center justify-center border-e border-[color-mix(in_oklch,var(--accent)_28%,var(--canvas))] bg-[color-mix(in_oklch,var(--accent)_10%,var(--canvas))] text-(--accent)">
    <x-icon name="info" :size="20" />
  </span>

  <div class="min-w-0 px-4 py-3.5">
    <p class="text-base font-semibold text-ink">{{ $title }}</p>

    <p class="mt-1 text-base leading-relaxed text-pretty text-body">{{ $slot }}</p>
  </div>
</div>
