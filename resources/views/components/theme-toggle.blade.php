{{-- The light and dark switch. --}}
{{-- @var string $tone --}}
@props([
  'tone' => 'default',
])

@php
  $colours = $tone === 'inverted'
    ? 'text-on-primary/70 hover:bg-on-primary/10 hover:text-on-primary'
    : 'text-muted hover:bg-hover hover:text-ink';
@endphp

<button
  x-data
  type="button"
  @click="$store.theme.toggle()"
  aria-label="{{ __('Switch between the light and dark theme') }}"
  title="{{ __('Switch between the light and dark theme') }}"
  {{
    $attributes->class([
      'relative inline-flex size-8 cursor-pointer items-center justify-center rounded-md',
      $colours,
      "after:absolute after:-inset-1.5 after:content-['']",
      'transition-[background-color,color,transform] duration-(--duration-fast) ease-out',
      '',
      'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none',
    ])
  }}
>
  <x-icon name="sun" :size="17" :stroke-width="1.5" class="dark:hidden" />
  <x-icon name="moon" :size="17" :stroke-width="1.5" class="hidden dark:block" />
</button>
