{{-- The light and dark switch. --}}
{{-- @var string $tone --}}
@props([
  'tone' => 'default',
])

@php
  $colours = $tone === 'inverted'
    ? 'text-on-admin-bar-muted hover:bg-on-admin-bar/10 hover:text-on-admin-bar focus-ring-on-admin-bar'
    : 'text-muted hover:bg-hover hover:text-ink focus-ring';
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
      "after:absolute after:-inset-1.5",
      'transition duration-(--duration-fast) ease-out',
    ])
  }}
>
  <x-icon name="sun" :size="17" :stroke-width="1.5" class="dark:hidden" />
  <x-icon name="moon" :size="17" :stroke-width="1.5" class="hidden dark:block" />
</button>
